<?php

class Crawler 
{
    public function fetch($url)
    {
        $return_var = 0;
        $output_file = tempnam('', '');
        $fp = fopen($output_file, 'w');
        sleep(1);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_exec($curl);
        fclose($fp);

        $info = curl_getinfo($curl);
        if (200 !== $info['http_code']) {
            return false;
        }
        return $output_file;
    }

    public function convert($file)
    {
        $output_file = tempnam('', '');
        system('pdftotext -enc UTF-8 ' . escapeshellarg($file) . ' ' . escapeshellarg($output_file));
        $content = file_get_contents($output_file);
        $content = preg_replace('/.*\n.*\n.*\n核准設立日期\n/m', '', $content, 1);
        $content = preg_replace('/\d+\n第 頁\n.*\n.*\n.*\n.*\n核准設立日期/m', '==分隔線==', $content);
        file_put_contents($output_file, $content);

        $ids = array();
        $fp = fopen($output_file, 'r');
        while (false !== ($line = fgets($fp))) {
            $terms = explode(' ', trim($line));
            foreach ($terms as $term) {
                if (preg_match('/^\d{8,8}$/', $term)) {
                    $ids[] = $term;
                }
            }
        }
        fclose($fp);
        unlink($output_file);
        return $ids;
    }

    public function crawlerBussiness($year, $month)
    {
        $orginations = array(
            '376570000A' => '基隆市政府',
            '376410000A' => '新北市政府',
            '379100000G' => '台北市政府',
            '376430000A' => '桃園縣政府',
            '376440000A' => '新竹縣政府',
            '376580000A' => '新竹市政府',
            '376450000A' => '苗栗縣政府',
            '376460000A' => '台中縣政府',
            '376590000A' => '台中市政府',
            '376480000A' => '南投縣政府',
            '376470000A' => '彰化縣政府',
            '376490000A' => '雲林縣政府',
            '376500000A' => '嘉義縣政府',
            '376600000A' => '嘉義市政府',
            '376510000A' => '台南縣政府',
            '376610000A' => '台南市政府',
            '376520000A' => '高雄縣政府',
            '383100000G' => '高雄市政府',
            '376530000A' => '屏東縣政府',
            '376420000A' => '宜蘭縣政府',
            '376550000A' => '花蓮縣政府',
            '376540000A' => '台東縣政府',
            '376560000A' => '澎湖縣政府',
            '371010000A' => '金門縣政府',
            '371030000A' => '連江縣政府',
        );

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
                $file = self::fetch($url);
                if (false === $file) {
                    trigger_error("Fetch failed: {$ori_id}-{$type_id}-{$year}-{$month}", E_USER_WARNING);
                    continue;
                }
                $ret += self::convert($file);
                unlink($file);
            }
        }
        return $ret;
    }

    public function crawlerMonth($year, $month)
    {
        $orginations = array(
            'AL' => '全國不分區',
            'MO' => '經濟部商業司',
            'CT' => '經濟部中部辦公室',
            'DO' => '臺北市商業處',
            'NT' => '新北市政府經濟發展局',
            'KC' => '高雄市政府經濟發展局',
            'TN' => '臺南市政府經濟發展局',
            'SI' => '科學工業園區管理區',
            'CS' => '中部科學工業園區管理局',
            'ST' => '南部科學工業園區管理局',
            'EP' => '經濟部加工出口區管理處',
            'PT' => '屏東農業生物技術園區籌備處',
        );

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
                $ret += self::convert($file);
                unlink($file);
            }
        }
        return $ret;
    }
}
