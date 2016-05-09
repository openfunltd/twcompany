<?php

class Crawler 
{
    public static function fetch($url)
    {
        $return_var = 0;
        $output_file = tempnam('', '');
        $fp = fopen($output_file, 'w');
        sleep(1);
        error_log($url);
        for ($i = 0; $i < 3; $i ++) {
            $curl = curl_init($url);
            if (getenv('PROXY_URL')) {
                curl_setopt($curl, CURLOPT_PROXY, getenv('PROXY_URL'));
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_FILE, $fp);
            curl_exec($curl);
            $info = curl_getinfo($curl);
            if ($info["http_code"] == 200) {
                break;
            }
        }
        if ($i == 3) {
            throw new Exception("{$url} failed");
        }
        error_log("done " . $url);
        fclose($fp);

        $info = curl_getinfo($curl);
        if (200 !== $info['http_code']) {
            return false;
        }
        return $output_file;
    }

    public static function convert($file)
    {
        $output_file = tempnam('', '');
        system('pdftotext -enc UTF-8 ' . escapeshellarg($file) . ' ' . escapeshellarg($output_file));

        $ids = array();
        $content = file_get_contents($output_file);
        preg_match_all('/\d{8,8}/', $content, $matches);
        foreach ($matches[0] as $match) {
            $ids[] = $match;
        }
        unlink($output_file);
        return $ids;
    }

    public static function crawlerBussiness($year, $month)
    {
        $content = file_get_contents('http://gcis.nat.gov.tw/moeadsBF/bms/report.jsp');
        $content = iconv('big5', 'utf-8', $content);
        preg_match('#<select name="area">(.*?)</select>#s', $content, $matches);
        preg_match_all('#<option value="(.*?)">(.*?)</option>#', $matches[1], $matches);

        $orginations = array();
        foreach ($matches[1] as $k => $id) {
            $orginations[$id] = $matches[2][$k];
        }

        $types = array(
            'setup' => '設立',
            'change' => '變更',
            'rest' => '解散',
        );

        $ret = array();
        foreach ($orginations as $ori_id => $type_name) {
            foreach ($types as $type_id => $type_name) {
                $yearmonth = sprintf("%03d%02d", $year, $month);
                $url = "http://gcis.nat.gov.tw/moeadsBF/cmpy/reportAction.do?method=report&reportClass=bms&subPath={$yearmonth}&fileName={$ori_id}{$type_id}{$yearmonth}.pdf";
                error_log("Fetch {$url}");
                $file = self::fetch($url);
                if (false === $file) {
                    trigger_error("Fetch failed: {$ori_id}-{$type_id}-{$year}-{$month}", E_USER_WARNING);
                    continue;
                }
                $ret = array_merge($ret, self::convert($file));
                unlink($file);
            }
        }
        return $ret;
    }

    public static function crawlerMonth($year, $month)
    {
        $content = file_get_contents('http://gcis.nat.gov.tw/pub/cmpy/reportReg.jsp');
        $content = iconv('big5', 'utf-8', $content);
        preg_match('#<select name="org">(.*?)</select>#s', $content, $matches);
        preg_match_all('#<option value="(.*?)">(.*?)</option>#', $matches[1], $matches);

        $orginations = array();
        foreach ($matches[1] as $k => $id) {
            $orginations[$id] = $matches[2][$k];
        }

        $types = array(
            'S' => '設立',
            'C' => '變更',
            'D' => '解散',
        );

        $ret = array();
        foreach ($orginations as $ori_id => $type_name) {
            foreach ($types as $type_id => $type_name) {
                $yearmonth = sprintf("%03d%02d", $year, $month);
                $url = "http://gcis.nat.gov.tw/pub/cmpy/reportAction.do?method=report&reportClass=cmpy&subPath=${yearmonth}&fileName=${yearmonth}{$ori_id}{$type_id}.pdf";
                $file = self::fetch($url);
                if (false === $file) {
                    trigger_error("Fetch failed: {$ori_id}-{$type_id}-{$year}-{$month}", E_USER_WARNING);
                    continue;
                }
                $ret = array_merge($ret, self::convert($file));
                unlink($file);
            }
        }
        return $ret;
    }
}
