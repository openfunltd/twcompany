台灣公司登記資料
================
這裡是 [台灣公司資料](https://company.g0v.ronny.tw/) 的程式碼，關於資料內容詳細說明，可見 [台灣公司資料](https://company.g0v.ronny.tw/) 首頁說明，或見 webdata/view/index/index.phtml 內容。

License
-------
* 程式為 BSD License 授權
* [pixframework](https://github.com/pixnet/pixframework) BSD License
* [jQuery](https://jquery.org/license/) BSD License
* [Bootstrap](https://getbootstrap.com/docs/4.0/about/license/) MIT License

安裝說明
--------
* 需要有 php, php-mysqlnd, php-mbstring, php-json
* git clone https://github.com/ronnywang/twcompany
* cd twcompany/webdata
* cp config.sample.php config.php
  * 修改 DATABASE\_URL 和 SEARCH\_URL
* php webdata/init-db.php
  * 建立起需要的資料表
* php webdata/import-data.php
  * 從 [資料打包](http://ronnywang-twcompany.s3-website-ap-northeast-1.amazonaws.com/index.html) 下載最新打包檔匯入資料庫
* 匯入資料庫
  * 準備好你的 elastic index (可能是 http://localhost:9200/company)
  * 到 webdata/models/elastic 下
    * curl -XPUT 'http://localhost:9200/company/company/_mapping?include_type_name=true' -H 'Content-Type: application/json' -d '@company.json'

資料表說明
----------
* 經濟部商業司資料
    * columngroup: 定義資料欄位組合
    * unit: 各單位資料表
    * unit_data: 各單位欄位的值
    * unitchangelog: 各單位欄位值變動記錄
* 財政部資料
    * fia_columngroup: 定義資料欄位組合
    * fia_unit_data: 各單位欄位的值
    * fia_unitchangelog: 各單位欄位值變動記錄
* 其他
    * key_value: 一些環境變數組合，例如最近一次更新時間

相關程式說明
------------
* webdata/scripts/
  * php crawler.php \[compnay|bussiness] YYYMM
    * 從 [經濟部商業司 公司資料異動清冊](https://serv.gcis.nat.gov.tw/pub/cmpy/reportReg.jsp) 抓取上個月設立、變更、解散公司或商號更新資料
    * 每月執行一次，大約在每月 5 號左右
  * php update-tax.php
    * 從 [財政部 全國營業(稅籍)登記資料集](https://data.gov.tw/dataset/9400) 抓取每日更新資料
    * 每日執行一次，下載完整資料後會與資料庫比對有更改的記錄，有更改的部份會順便更新經濟部資料
  * php dump-gcis-history.php
    * 匯出商業司變更記錄，[位置](http://ronnywang-twcompany.s3-website-ap-northeast-1.amazonaws.com/gcis-history/)
  * php dump-index.php
    * 匯出列表檔案，[位置](http://ronnywang-twcompany.s3-website-ap-northeast-1.amazonaws.com/index.csv.gz)
  * php dump-tax.php
    * 匯出財政部變更記錄，[位置](http://ronnywang-twcompany.s3-website-ap-northeast-1.amazonaws.com/tax-history)
  * php dump.php
    * 匯出完整經濟部打包檔，[位置](http://ronnywang-twcompany.s3-website-ap-northeast-1.amazonaws.com/files/)

相關連結
--------
* [台灣公司關係圖](https://company-graph.g0v.ronny.tw)
* [台灣公司關係圖程式](https://github.com/ronnywang/company-graph)
