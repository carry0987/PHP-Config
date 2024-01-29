<?php
namespace carry0987\Config;

use carry0987\Config\Exceptions\ConfigException;
use PDO;
use PDOException;

class Config
{
    private ?PDO $connectdb;
    private ?object $redis;
    private static string $tableName = 'config';
    private static array $configIndex;

    public function __construct(PDO $connectdb)
    {
        $this->connectdb = $connectdb;
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

    public function setRedis(?object $redis): self
    {
        $this->redis = $redis ?: null;

        return $this;
    }

    public function addConfig(string $config_param, string|array $config_value): bool
    {
        $config_value = (is_array($config_value)) ? serialize($config_value) : $config_value;
        if ($this->redis) {
            $this->redis->setHashValue(self::$tableName, $config_param, $config_value);
        }
        if (!isset(self::$configIndex[$config_param])) {
            return $this->addParamConfig($config_param, $config_value);
        }

        return $this->executeStatement('add_config', [$config_param, $config_value]);
    }

    public function getConfig(string $config_param, bool $only_value = false)
    {
        if (!isset(self::$configIndex[$config_param])) {
            return $this->getParamConfig($config_param, $only_value);
        }
        if ($this->redis) {
            $redis_config = $this->redis->getHashValue(self::$tableName, $config_param);
            if ($redis_config) {
                $result['param'] = $config_param;
                $result['value'] = unserialize($redis_config);
                return ($only_value === true) ? $result['value'] : $result;
            }
        }

        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery('get_config'));
            $stmt->bindValue(1, self::$configIndex[$config_param], PDO::PARAM_INT);
            $stmt->execute();
            $read_row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        // Get config
        $result['param'] = $read_row['param'];
        $value = (string) $read_row['value'];
        if ($this->redis) $this->redis->setHashValue(self::$tableName, $result['param'], $value);
        $result['value'] = unserialize($value);

        return ($only_value === true) ? $result['value'] : $result;
    }

    public function updateConfig(string $config_param, string|array $config_value): bool
    {
        $config_value = (is_array($config_value)) ? serialize($config_value) : $config_value;

        if ($this->redis) {
            $this->redis->setHashValue(self::$tableName, $config_param, $config_value);
        }
        if (!isset(self::$configIndex[$config_param])) {
            return $this->updateParamConfig($config_param, $config_value);
        }

        $result = false;

        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery('update_config'));
            $stmt->bindValue(1, $config_value, PDO::PARAM_STR);
            $stmt->bindValue(2, self::$configIndex[$config_param], PDO::PARAM_INT);
            $result = $stmt->execute();
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    private function addParamConfig(string $config_param, string|array $config_value): bool
    {
        $config_value = (is_array($config_value)) ? serialize($config_value) : $config_value;
        $result = false;

        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery('add_param_config'));
            $stmt->bindValue(1, $config_param, PDO::PARAM_STR);
            $stmt->bindValue(2, $config_value, PDO::PARAM_STR);
            $result = $stmt->execute();
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    private function getParamConfig(string $config_param, bool $only_value = false)
    {
        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery('get_param_config'));
            $stmt->bindValue(1, $config_param, PDO::PARAM_STR);
            $stmt->execute();
            $read_row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        // Get config
        $result['param'] = $config_param;
        $value = (string) $read_row['value'];
        $result['value'] = unserialize($value);

        return ($only_value === true) ? $result['value'] : $result;
    }

    private function updateParamConfig(string $config_param, string|array $config_value): bool
    {
        $config_value = (is_array($config_value)) ? serialize($config_value) : $config_value;
        $result = false;

        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery('update_param_config'));
            $stmt->bindValue(1, $config_value, PDO::PARAM_STR);
            $stmt->bindValue(2, $config_param, PDO::PARAM_STR);
            $result = $stmt->execute();
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    private function executeStatement(string $queryName, array $params): bool
    {
        $result = false;

        try {
            $stmt = $this->connectdb->prepare(self::getConfigQuery($queryName));
            foreach ($params as $index => $param) {
                $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $result = $stmt->execute();
        } catch (PDOException $e) {
            throw new ConfigException($e->getMessage(), $e->getCode());
        }

        return $result;
    }

    private static function serializeValue(string|array $value): string
    {
        return is_array($value) ? serialize($value) : $value;
    }

    private static function unserializeValue(string $value)
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
