<?php

include(__DIR__ . '/../init.inc.php');

class CustomCrawler
{
    public function wrong_argv()
    {
        die("Usage: php crawler.php <company|bussiness> <YYY> <MM>\n");
    }

    public function main($argv)
    {
        Pix_Table::$_save_memory = true;
        list(, $type, $year, $month) = $argv;
        if (!in_array($type, array('company', 'bussiness'))) {
            return $this->wrong_argv();
        }
        $year = intval($year);
        $month = intval($month);

        if (!intval($year) or !intval($month)) {
            return $this->wrong_argv();
        }

        if ('company' == $type) {
            $ids = Crawler::crawlerMonth($year, $month);
            $ids = array_unique($ids);
            file_put_contents('ids', implode("\n", $ids));
            foreach ($ids as $id) {
                $u = Updater::update($id);
                if ($u) {
                    $u->updateSearch();
                }
            }
        } else {
            $ids = Crawler::crawlerBussiness($year, $month);
            $ids = array_unique($ids);
            file_put_contents('ids', implode("\n", $ids));
            foreach ($ids as $id) {
                $u = Updater::updateBussiness($id);
                if ($u) {
                    $u->updateSearch();
                }
            }
        }
    }
}

$c = new CustomCrawler;
$c->main($_SERVER['argv']);
