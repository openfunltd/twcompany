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
