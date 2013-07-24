<?php

class UnitLocation extends Pix_Table
{
    public static function searchUnit($min_lng, $min_lat, $max_lng, $max_lat)
    {
        $min_lng = floatval($min_lng);
        $max_lng = floatval($max_lng);
        $min_lat = floatval($min_lat);
        $max_lat = floatval($max_lat);
        $text = "POLYGON(({$min_lng} {$min_lat},{$min_lng} {$max_lat},{$max_lng} {$max_lat},{$max_lng} {$min_lat},{$min_lng} {$min_lat}))";

        $sql = "SELECT id, ST_X(geo::geometry) AS lng, ST_Y(geo::geometry) AS lat FROM unit_location WHERE ST_Intersects(geo, ST_GeographyFromText('{$text}'))";
        $db = self::getDb();

        $res = $db->query($sql);
        $ret = array();
        while ($row = $res->fetch_assoc()) {
            $ret[] = array(
                'id' => intval($row['id']),
                'lat' => floatval($row['lat']),
                'lng' => floatval($row['lng']),
            );
        }
        $res->free_result();

        return $ret;
    }

    public function init()
    {
        $this->_name = 'unit_location';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int');
        $this->_columns['geo'] = array('type' => 'geography', 'modifier' => array('POINT', 4326));
    }

    public function _getDb()
    {
        if (!preg_match('#pgsql://([^:]*):([^@]*)@([^/]*)/(.*)#', strval(getenv('PGSQL_DATABASE_URL')), $matches)) {
            die('pgsql only');
        }
        $options = array(
            'host' => $matches[3],
            'user' => $matches[1],
            'password' => $matches[2],
            'dbname' => $matches[4],
        );
        return new Pix_Table_Db_Adapter_PgSQL($options);
    }
}
