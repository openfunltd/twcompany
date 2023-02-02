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
        if (!in_array($type, array('company', 'bussiness', 'company-continue', 'bussiness-continue'))) {
            return $this->wrong_argv();
        }

        if (in_array($type, array('company', 'bussiness'))) {
            $year = intval($year);
            $month = intval($month);

            if (!intval($year) or !intval($month)) {
                return $this->wrong_argv();
            }
        }

        if ('company' == $type) {
            $ids = Crawler::crawlerMonth($year, $month);
            $ids = array_unique($ids);
            file_put_contents('ids', implode("\n", $ids));
            $i = 1;
            $total = count($ids);
            foreach ($ids as $id) {
                fwrite(STDERR, chr(27) . "k{$i}/{$total}" . chr(27) . "\\");
                error_log($i . '/' . count($ids));
                $i ++;
                $u = Updater2::update($id);
                if ($u) {
                    $u->updateSearch();
                }
            }
        } elseif ('company-continue' == $type) {
            $ids = explode("\n", file_get_contents('ids'));
            $pos = array_search($year, $ids);
            var_dump($pos);
            if (false === $pos) {
                return $this->wrong_argv();
            }
            $i = $pos;
            $total = count($ids);
            foreach (array_slice($ids, $pos - 1) as $id) {
                fwrite(STDERR, chr(27) . "k{$i}/{$total}" . chr(27) . "\\");
                error_log($i . '/' . count($ids));
                $i ++;
                $u = Updater2::update($id);
                if ($u) {
                    $u->updateSearch();
                }
            }
        } elseif ('bussiness-continue' == $type) {
            $ids = explode("\n", file_get_contents('ids'));
            $pos = array_search($year, $ids);
            var_dump($pos);
            if (false === $pos) {
                return $this->wrong_argv();
            }
            $i = $pos;
            $total = count($ids);
            foreach (array_slice($ids, $pos - 1) as $id) {
                fwrite(STDERR, chr(27) . "k{$i}/{$total}" . chr(27) . "\\");
                error_log($i . '/' . count($ids));
                $i ++;
                $u = Updater2::updateBussiness($id);
                if ($u) {
                    $u->updateSearch();
                }
            }
        } else {
            $ids = Crawler::crawlerBussiness($year, $month);
            $ids = array_unique($ids);
            file_put_contents('ids', implode("\n", $ids));
            $i = 1;
            $total = count($ids);
            foreach ($ids as $id) {
                fwrite(STDERR, chr(27) . "k{$i}/{$total}" . chr(27) . "\\");
                error_log($i . '/' . count($ids));
                $i ++;
                $u = Updater2::updateBussiness($id);
                if ($u) {
                    $u->updateSearch();
                }
            }
        }
        Unit::refreshUpdatedStatus();
    }
}

$c = new CustomCrawler;
$c->main($_SERVER['argv']);
