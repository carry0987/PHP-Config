SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/* Create global config */
CREATE TABLE IF NOT EXISTS `config` (
    `id` int(3) UNSIGNED NOT NULL AUTO_INCREMENT,
    `param` varchar(150) NOT NULL,
    `value` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX (`param`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration Table';

INSERT INTO `config` (`id`, `param`, `value`) VALUES
(1, 'demo_config', 'a:8:{s:8:"web_name";s:11:"Hello World";s:15:"web_description";s:21:"Welcome To RedLight !";s:12:"web_language";s:5:"zh_TW";s:12:"web_timezone";s:11:"Asia/Taipei";s:11:"maintenance";i:1;s:11:"description";s:22:"Welcome To The World !";s:8:"language";s:5:"en_US";s:8:"timezone";s:11:"Asia/Taipei";}');
