<?php
namespace carry0987\Config;

use carry0987\Config\Exceptions\ConfigException;
use carry0987\Redis\RedisTool;
use PDO;
use PDOStatement;
use PDOException;

class Config
{
    private ?PDO $connectdb;
    private ?object $redis = null;
    private static string $tableName = 'config';
    private static array $configIndex;

    public function __construct(PDO $connectdb)
    {
        $this->connectdb = $connectdb;

        return $this;
    }

    public function setTableName(string $tableName): self
    {
        self::$tableName = $tableName;

        return $this;
    }

    public function setIndexList(array $configIndex): self
    {
        self::$configIndex = $configIndex;

        return $this;
    }

    public function setRedis(?RedisTool $redis): self
    {
        $this->redis = $redis;

        return $this;
    }

    public function addConfig(string $config_param, string|array $config_value): bool
    {
        if ($this->getConfig($config_param, true) !== false) {
            return $this->updateConfig($config_param, $config_value);
        }
        if ($this->redis) {
            $this->redis->setHashValue(self::$tableName, $config_param, self::serializeValue($config_value));
        }
        if (!isset(self::$configIndex[$config_param])) {
            return $this->addParamConfig($config_param, $config_value);
        }

        return $this->executeUpdate('add_config', [$config_param, $config_value]);
    }

    public function getConfig(string $config_param, bool $only_value = true): string|array|bool
    {
        if (!isset(self::$configIndex[$config_param])) {
            return $this->getParamConfig($config_param, $only_value);
        }
        if ($this->redis) {
            $redis_config = $this->redis->getHashValue(self::$tableName, $config_param);
            if ($redis_config) {
                $result['param'] = $config_param;
                $result['value'] = self::unserializeValue($redis_config);
                $result['redis'] = true;
                return ($only_value === true) ? $result['value'] : $result;
            }
        }

        try {
            $stmt = $this->executeQuery('get_config', [self::$configIndex[$config_param]], true);
            $read_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$read_row) return false;
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        // Get config
        $result['param'] = $read_row['param'];
        $value = (string) $read_row['value'];

        // Update redis
        if ($this->redis) $this->redis->setHashValue(self::$tableName, $result['param'], $value);

        // Return config
        $result['value'] = self::unserializeValue($value);

        return ($only_value === true) ? $result['value'] : $result;
    }

    public function updateConfig(string $config_param, string|array $config_value): bool
    {
        $config_value = self::serializeValue($config_value);
        if ($this->redis) {
            $this->redis->setHashValue(self::$tableName, $config_param, $config_value);
        }
        if (!isset(self::$configIndex[$config_param])) {
            return $this->updateParamConfig($config_param, $config_value);
        }

        $result = false;

        try {
            $result = $this->executeUpdate('update_config', [$config_value, self::$configIndex[$config_param]]);
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    private function addParamConfig(string $config_param, string|array $config_value): bool
    {
        $config_value = self::serializeValue($config_value);
        $result = false;

        try {
            $result = $this->executeUpdate('add_param_config', [$config_param, $config_value]);
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    private function getParamConfig(string $config_param, bool $only_value = false): string|array|bool
    {
        try {
            $stmt = $this->executeQuery('get_param_config', [$config_param], true);
            $read_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$read_row) return false;
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        // Get config
        $result['param'] = $config_param;
        $value = (string) $read_row['value'];
        $result['value'] = self::unserializeValue($value);

        return ($only_value === true) ? $result['value'] : $result;
    }

    private function updateParamConfig(string $config_param, string|array $config_value): bool
    {
        $config_value = self::serializeValue($config_value);
        $result = false;

        try {
            $result = $this->executeUpdate('update_param_config', [$config_value, $config_param]);
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    private function executeUpdate(string $queryName, array $params): bool
    {
        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery($queryName));
            foreach ($params as $index => $param) {
                $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }
    }

    private function executeQuery(string $queryName, array $params): PDOStatement
    {
        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery($queryName));
            foreach ($params as $index => $param) {
                $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }
    }

    private static function serializeValue(string|array $value): string
    {
        return serialize($value);
    }

    private static function unserializeValue(string $value): string|array
    {
        try {
            $data = unserialize($value);
        } catch (\Throwable $th) {
            $data = false;
        }

        return $data === false ? $value : $data;
    }

    private static function getConfigQuery(string $param): string
    {
        switch ($param) {
            case 'get_config':
                $query = 'SELECT param, value FROM CONFIG_TABLE WHERE id = ?';
                break;
            case 'add_config':
            case 'add_param_config':
                $query = 'INSERT INTO CONFIG_TABLE (param, value) VALUES (?,?)';
                break;
            case 'update_config':
                $query = 'UPDATE CONFIG_TABLE SET value = ? WHERE id = ?';
                break;
            case 'get_param_config':
                $query = 'SELECT value FROM CONFIG_TABLE WHERE param = ?';
                break;
            case 'update_param_config':
                $query = 'UPDATE CONFIG_TABLE SET value = ? WHERE param = ?';
                break;
            default:
                throw new ConfigException('Unsupported Parameter');
        }

        // Replace table name
        $query = str_replace('CONFIG_TABLE', self::$tableName, $query);

        return $query;
    }
}
