<?php

namespace PrestaShop\Module\AdresValidatie\Service;

use \Db;

class DatabaseService
{
    private static $addressesTableName = _DB_PREFIX_ . 'adresvalidatie_addresses';
    private static $temporaryAddressesTableName = _DB_PREFIX_ . 'adresvalidatie_addresses_new';

    public static function migrateUp()
    {
        return self::createTable(self::$addressesTableName);
    }

    public static function migrateDown()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . self::$addressesTableName . '`')
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . self::$temporaryAddressesTableName . '`')
            ;
    }

    public static function createTemporaryTable()
    {
        return self::createTable(self::$temporaryAddressesTableName);
    }

    private static function createTable($tableName)
    {
        return Db::getInstance()->execute( "
CREATE TABLE IF NOT EXISTS `$tableName` (
    `id_adresvalidatie_address` INT(11) NOT NULL AUTO_INCREMENT,
    `postcode` varchar(6) NOT NULL,
    `huisnummer` mediumint(5) NOT NULL,
    `huisletter` varchar(1) NULL,
    `toevoeging` varchar(8) NULL,
    `straatnaam` varchar(100) NULL,
    `woonplaats_naam` varchar(100) NULL,
    PRIMARY KEY (`id_adresvalidatie_address`),
    INDEX (`postcode`),
    INDEX (`huisnummer`),
    INDEX (`huisletter`),
    INDEX (`toevoeging`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;
        ");
    }

    public static function replaceWithTemporaryTable()
    {
        $db = Db::getInstance();
        $result = $db->executeS("SHOW TABLE STATUS LIKE '"._DB_PREFIX_."adresvalidatie_addresses'");
        $useTransactions = is_array($result) && is_array($result[0]) && !empty($result[0]['Engine']) && $result[0]['Engine'] == 'InnoDB';

        if ($useTransactions) {
            $db->execute('START TRANSACTION');
        }
        $db->execute('DROP TABLE IF EXISTS `' . self::$addressesTableName . '`');
        $db->execute('RENAME TABLE `' . self::$temporaryAddressesTableName . '` TO `' . self::$addressesTableName . '`');
        if ($useTransactions) {
            $db->execute('COMMIT');
        }
    }

    public static function insertIntoTemporaryTable($values) {
        $query = "INSERT INTO `" . self::$temporaryAddressesTableName . "` (`postcode`, `huisnummer`, `huisletter`, `toevoeging`, `straatnaam`, `woonplaats_naam`) VALUES ";
        foreach($values as $key => $row) {
            $values[$key] = '(' .
                "'$row[0]', " . // postcode
                "$row[1], " . // huisnummer
                (empty($row[2]) ? 'NULL' : "'$row[2]'") . "," . // huisletter
                (empty($row[3]) ? 'NULL' : "'$row[3]'") . "," . // toevoeging
                "'" . pSQL($row[4]). "'," . // straatnaam
                "'" . pSQL($row[5]). "'" . // woonplaats_naam
                ')';
        }
        $query .= implode(', ', $values);
        error_log($query);

        return Db::getInstance()->execute($query);
    }

    public static function findAddress($postcode, $housenumber, $houseletter, $addition) {
        $query = new DbQuery();
        $query->select('*');
        $query->from(self::$addressesTableName);
        $query->where('`postcode` = "' . pSQL($postcode) . '"');
        $query->where('`huisnummer` = ' . (int)$housenumber);
        if ($houseletter !== null) {
            $query->where('`huisletter` = "' . pSQL($houseletter) . '"');
        } else {
            $query->where('`huisletter` IS NULL');
        }
        if ($addition !== null) {
            $query->where('`toevoeging` = "' . pSQL($addition) . '"');
        } else {
            $query->where('`toevoeging` IS NULL');
        }
        return Db::getInstance()->getRow($query);
    }
}