<?php

/**
 * @OA\Info(title="台灣公司資料API", version="0.0.1")
 * 
 */
class ApiController extends Pix_Controller
{
    public function init()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
    }

    /**
     * @OA\Get(
     *   path="/api/bulkquery", summary="依統編批量查詢公司資料", description="依統編批量查詢公司資料",
     *   @OA\Parameter(name="ids", in="query", description="統一編號（以分號分隔）", required=true,
     *     @OA\Schema(type="string"), example="89285269;24960372"
     *   ),
     *   @OA\Response( response="200", description="批量查詢公司資料",
     *     @OA\JsonContent( type="object",
     *       @OA\Property(property="公司統編號碼", type="object",
     *         @OA\Property(property="公司狀況", type="string", example="核准設立"),
     *         @OA\Property(property="公司名稱", type="string", example="ＯＯ有限公司"),
     *         @OA\Property(property="資本總額(元)", type="string", example="1,600,000"),
     *         @OA\Property(property="代表人姓名", type="string", example="王ＯＯ"),
     *         @OA\Property(property="公司所在地", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *         @OA\Property(property="登記機關", type="string", example="新北市政府"),
     *         @OA\Property(property="核准設立日期", type="object",
     *           @OA\Property(property="year", type="integer", example=2012),
     *           @OA\Property(property="month", type="integer", example=12),
     *           @OA\Property(property="day", type="integer", example=21),
     *         ),
     *         @OA\Property(property="最後核准變更日期", type="object",
     *           @OA\Property(property="year", type="integer", example=2012),
     *           @OA\Property(property="month", type="integer", example=12),
     *           @OA\Property(property="day", type="integer", example=21),
     *         ),
     *         @OA\Property(property="所營事業資料", type="array",
     *           @OA\Items(type="array", @OA\Items(type="string")),
     *           example={{"I301010", "資訊軟體服務業"}},
     *         ),
     *         @OA\Property(property="董監事名單", type="array",
     *           @OA\Items(type="object",
     *             @OA\Property(property="序號", type="string", example="0001"),
     *             @OA\Property(property="職稱", type="string", example="董事"),
     *             @OA\Property(property="姓名", type="string", example="王ＯＯ"),
     *             @OA\Property(property="所代表法人", type="array",
     *               @OA\Items(type="string"),
     *              example={"89285269（公司統編）", "ＯＯ有限公司"},
     *             ),
     *             @OA\Property(property="出資額", type="string", example="1,600,000"),
     *           ),
     *         ),
     *         @OA\Property(property="經理人名單", type="array",
     *           @OA\Items(type="object",
     *             @OA\Property(property="序號", type="string", example="0001"),
     *             @OA\Property(property="姓名", type="string", example="李ＯＯ"),
     *             @OA\Property(property="到職日期", type="object",
     *               @OA\Property(property="year", type="integer", example=2012),
     *               @OA\Property(property="month", type="integer", example=12),
     *               @OA\Property(property="day", type="integer", example=21),
     *             ),
     *           ),
     *         ),
     *         @OA\Property(property="章程所訂外文公司名稱", type="string", example="OpenFun Company Limited"),
     *         @OA\Property(property="複數表決權特別股", type="string", example="無"),
     *         @OA\Property(property="對於特定事項具否決權特別股", type="string", example="無"),
     *         @OA\Property(property="每股金額(元)", type="integer", example=10),
     *         @OA\Property(property="已發行股份總數(股)", type="string", example="3,546,562,881"),
     *         @OA\Property(property="財政部", type="object",
     *           @OA\Property(property="營業人名稱", type="string", example="ＯＯ有限公司"),
     *           @OA\Property(property="資本額", type="string", example="1600000"),
     *           @OA\Property(property="設立日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="使用統一發票", type="string", example="Y"),
     *           @OA\Property(property="行業", type="array", @OA\Items(type="string"), example={"639099", "未分類其他資訊服務"}),
     *           @OA\Property(property="營業地址", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *           @OA\Property(property="負責人姓氏", type="string", example="王"),
     *           @OA\Property(property="總機構統一編號", type="string", example="89285269"),
     *           @OA\Property(property="組織別名稱", type="string", example="有限公司"),
     *         ),
     *         @OA\Property(property="統一編號", type="string", example="89285269"),
     *       ),
     *     ),
     *   ),
     * )
     */
    public function bulkqueryAction()
    {
        $ids = array_slice(explode(';', $_REQUEST['ids']), 0, 10000);
        $ret = new StdClass;
        foreach ($ids as $id) {
            if (!$id = intval($id)) {
                continue;
            }
            if (!$unit = Unit::find(intval($id))) {
                $data = new StdClass;
                $data->{'財政部'} = new StdClass;
                foreach (FIAUnitData::search(array('id' => $id)) as $unitdata) {
                    $data->{'財政部'}->{FIAColumnGroup::getColumnName($unitdata->column_id)} = json_decode($unitdata->value);
                }
                $ret->{$id} = $data;
            } else {
                $ret->{$id} = $unit->getData();
            }
        }
        return $this->json($ret);
    }

    /**
     * @OA\Get(
     *   path="/api/show/{id}", summary="查詢公司資料", description="查詢公司資料",
     *   @OA\Parameter(name="id", in="path", description="統一編號", required=true, @OA\Schema(type="string"), example="89285269"),
     *   @OA\Parameter(name="with_changelog", in="query", description="是否要顯示變更紀錄", required=false, @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="callback", in="query", description="callback function name", required=false, @OA\Schema(type="string")),
     *   @OA\Response( response="200", description="查詢公司資料",
     *     @OA\JsonContent( type="object",
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="公司狀況", type="string", example="核准設立"),
     *         @OA\Property(property="公司名稱", type="string", example="ＯＯ有限公司"),
     *         @OA\Property(property="資本總額(元)", type="string", example="1,600,000"),
     *         @OA\Property(property="代表人姓名", type="string", example="王ＯＯ"),
     *         @OA\Property(property="公司所在地", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *         @OA\Property(property="登記機關", type="string", example="新北市政府"),
     *         @OA\Property(property="核准設立日期", type="object",
     *           @OA\Property(property="year", type="integer", example=2012),
     *           @OA\Property(property="month", type="integer", example=12),
     *           @OA\Property(property="day", type="integer", example=21),
     *         ),
     *         @OA\Property(property="最後核准變更日期", type="object",
     *           @OA\Property(property="year", type="integer", example=2012),
     *           @OA\Property(property="month", type="integer", example=12),
     *           @OA\Property(property="day", type="integer", example=21),
     *         ),
     *         @OA\Property(property="所營事業資料", type="array",
     *           @OA\Items(type="array", @OA\Items(type="string")),
     *           example={{"I301010", "資訊軟體服務業"}},
     *         ),
     *         @OA\Property(property="董監事名單", type="array",
     *           @OA\Items(type="object",
     *             @OA\Property(property="序號", type="string", example="0001"),
     *             @OA\Property(property="職稱", type="string", example="董事"),
     *             @OA\Property(property="姓名", type="string", example="王ＯＯ"),
     *             @OA\Property(property="所代表法人", type="array",
     *               @OA\Items(type="string"),
     *               example={"89285269（公司統編）", "ＯＯ有限公司"},
     *             ),
     *             @OA\Property(property="出資額", type="string", example="1,600,000"),
     *           ),
     *         ),
     *         @OA\Property(property="經理人名單", type="array",
     *           @OA\Items(type="object",
     *             @OA\Property(property="序號", type="string", example="0001"),
     *             @OA\Property(property="姓名", type="string", example="李ＯＯ"),
     *             @OA\Property(property="到職日期", type="object",
     *               @OA\Property(property="year", type="integer", example=2012),
     *               @OA\Property(property="month", type="integer", example=12),
     *               @OA\Property(property="day", type="integer", example=21),
     *             ),
     *           ),
     *         ),
     *         @OA\Property(property="章程所訂外文公司名稱", type="string", example="OpenFun Company Limited"),
     *         @OA\Property(property="複數表決權特別股", type="string", example="無"),
     *         @OA\Property(property="對於特定事項具否決權特別股", type="string", example="無"),
     *         @OA\Property(property="每股金額(元)", type="integer", example=10),
     *         @OA\Property(property="已發行股份總數(股)", type="string", example="3,546,562,881"),
     *         @OA\Property(property="財政部", type="object",
     *           @OA\Property(property="營業人名稱", type="string", example="ＯＯ有限公司"),
     *           @OA\Property(property="資本額", type="string", example="1600000"),
     *           @OA\Property(property="設立日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="使用統一發票", type="string", example="Y"),
     *           @OA\Property(property="行業", type="array", @OA\Items(type="string"), example={"639099", "未分類其他資訊服務"}),
     *           @OA\Property(property="營業地址", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *           @OA\Property(property="負責人姓氏", type="string", example="王"),
     *           @OA\Property(property="總機構統一編號", type="string", example="89285269"),
     *           @OA\Property(property="組織別名稱", type="string", example="有限公司"),
     *         ),
     *         @OA\Property(property="統一編號", type="string", example="89285269"),
     *       ),
     *     ),
     *   ),
     * )
     */
    public function showAction()
    {
        list(, /*api*/, /*show*/, $id) = explode('/', $this->getURI());

        $ret = new StdClass;
        if (!$unit = Unit::find(intval($id))) {
            $data = new StdClass;
            $data->{'財政部'} = new StdClass;
            foreach (FIAUnitData::search(array('id' => $id)) as $unitdata) {
                $data->{'財政部'}->{FIAColumnGroup::getColumnName($unitdata->column_id)} = json_decode($unitdata->value);
            }
            $ret->data = $data;
            return $this->jsonp($ret, strval($_GET['callback']));
        }

        $ret->data = $unit->getData($_GET['with_changelog']);
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    /**
     * @OA\Get(
     *   path="/api/search", summary="依名稱或地址搜尋公司", description="依名稱或地址搜尋公司",
     *   @OA\Parameter(name="q", in="query", description="搜尋字串（預設依名稱搜尋，字串前綴加上 'address:' 則改為依地址搜尋）", required=true,
     *     @OA\Schema(type="string"),
     *     @OA\Examples(example="byName", value="歐噴", summary="依名稱搜尋"),
     *     @OA\Examples(example="byAddress", value="address:新北市板橋區", summary="依地址搜尋")
     *   ),
     *   @OA\Parameter(name="page", in="query", description="頁數", required=false, @OA\Schema(type="integer"), example=1),
     *   @OA\Parameter(name="alive_only", in="query", required=false, @OA\Schema(type="boolean"),
     *     description="只搜尋存續公司（此變數目前不會在依地址搜尋時生效）"),
     *   @OA\Parameter(name="callback", in="query", description="callback function name", required=false, @OA\Schema(type="string")),
     *   @OA\Response( response="200", description="依名稱或地址搜尋公司",
     *     @OA\JsonContent( type="object",
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(type="object", description="公司資料",
     *           @OA\Property(property="公司狀況", type="string", example="核准設立"),
     *           @OA\Property(property="公司名稱", type="string", example="ＯＯ有限公司"),
     *           @OA\Property(property="資本總額(元)", type="string", example="1,600,000"),
     *           @OA\Property(property="代表人姓名", type="string", example="王ＯＯ"),
     *           @OA\Property(property="公司所在地", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *           @OA\Property(property="登記機關", type="string", example="新北市政府"),
     *           @OA\Property(property="核准設立日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="最後核准變更日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="所營事業資料", type="array",
     *             @OA\Items(type="array", @OA\Items(type="string")),
     *             example={{"I301010", "資訊軟體服務業"}},
     *           ),
     *           @OA\Property(property="董監事名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="職稱", type="string", example="董事"),
     *               @OA\Property(property="姓名", type="string", example="王ＯＯ"),
     *               @OA\Property(property="所代表法人", type="array",
     *                 @OA\Items(type="string"),
     *                 example={"89285269（公司統編）", "ＯＯ有限公司"},
     *               ),
     *               @OA\Property(property="出資額", type="string", example="1,600,000"),
     *             ),
     *           ),
     *           @OA\Property(property="經理人名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="姓名", type="string", example="李ＯＯ"),
     *               @OA\Property(property="到職日期", type="object",
     *                 @OA\Property(property="year", type="integer", example=2012),
     *                 @OA\Property(property="month", type="integer", example=12),
     *                 @OA\Property(property="day", type="integer", example=21),
     *               ),
     *             ),
     *           ),
     *           @OA\Property(property="章程所訂外文公司名稱", type="string", example="OpenFun Company Limited"),
     *           @OA\Property(property="複數表決權特別股", type="string", example="無"),
     *           @OA\Property(property="對於特定事項具否決權特別股", type="string", example="無"),
     *           @OA\Property(property="每股金額(元)", type="integer", example=10),
     *           @OA\Property(property="已發行股份總數(股)", type="string", example="3,546,562,881"),
     *           @OA\Property(property="財政部", type="object",
     *             @OA\Property(property="營業人名稱", type="string", example="ＯＯ有限公司"),
     *             @OA\Property(property="資本額", type="string", example="1600000"),
     *             @OA\Property(property="設立日期", type="object",
     *               @OA\Property(property="year", type="integer", example=2012),
     *               @OA\Property(property="month", type="integer", example=12),
     *               @OA\Property(property="day", type="integer", example=21),
     *             ),
     *             @OA\Property(property="使用統一發票", type="string", example="Y"),
     *             @OA\Property(property="行業", type="array", @OA\Items(type="string"), example={"639099", "未分類其他資訊服務"}),
     *             @OA\Property(property="營業地址", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *             @OA\Property(property="負責人姓氏", type="string", example="王"),
     *             @OA\Property(property="總機構統一編號", type="string", example="89285269"),
     *             @OA\Property(property="組織別名稱", type="string", example="有限公司"),
     *           ),
     *           @OA\Property(property="統一編號", type="string", example="89285269"),
     *         ),
     *       ),
     *       @OA\Property(property="found", type="integer", example="1")
     *     ),
     *   ),
     * )
     */
    public function searchAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $alive_only = $_GET['alive_only'] ? true : false;
        if (preg_match('#^address:(.*)$#', $_GET['q'], $matches)) {
            $search_ret = (SearchLib::searchCompaniesByAddress($matches[1], $page, $alive_only));
        } else {
            $search_ret = (SearchLib::searchCompaniesByName($_GET['q'], $page, $alive_only));
        }
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total->value;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    /**
     * @OA\Get(
     *  path="/api/fund", summary="依董監事名單中「所代表法人」搜尋公司", description="依董監事名單中「所代表法人」搜尋公司",
     *  @OA\Parameter(name="q", in="query", description="搜尋字串", required=true, @OA\Schema(type="string"), example="王道商業銀行"),
     *  @OA\Parameter(name="page", in="query", description="頁數", required=false, @OA\Schema(type="integer"), example=1),
     *   @OA\Parameter(name="callback", in="query", description="callback function name", required=false, @OA\Schema(type="string")),
     *  @OA\Response( response="200", description="依董監事名單中所代表法人搜尋公司",
     *     @OA\JsonContent( type="object",
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(type="object", description="公司資料",
     *           @OA\Property(property="公司狀況", type="string", example="核准設立"),
     *           @OA\Property(property="公司名稱", type="string", example="ＯＯ有限公司"),
     *           @OA\Property(property="資本總額(元)", type="string", example="1,600,000"),
     *           @OA\Property(property="代表人姓名", type="string", example="王ＯＯ"),
     *           @OA\Property(property="公司所在地", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *           @OA\Property(property="登記機關", type="string", example="新北市政府"),
     *           @OA\Property(property="核准設立日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="最後核准變更日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="所營事業資料", type="array",
     *             @OA\Items(type="array", @OA\Items(type="string")),
     *             example={{"I301010", "資訊軟體服務業"}},
     *           ),
     *           @OA\Property(property="董監事名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="職稱", type="string", example="董事"),
     *               @OA\Property(property="姓名", type="string", example="王ＯＯ"),
     *               @OA\Property(property="所代表法人", type="array",
     *                 @OA\Items(type="string"),
     *                 example={"89285269（公司統編）", "ＯＯ有限公司"},
     *               ),
     *               @OA\Property(property="出資額", type="string", example="1,600,000"),
     *             ),
     *           ),
     *           @OA\Property(property="經理人名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="姓名", type="string", example="李ＯＯ"),
     *               @OA\Property(property="到職日期", type="object",
     *                 @OA\Property(property="year", type="integer", example=2012),
     *                 @OA\Property(property="month", type="integer", example=12),
     *                 @OA\Property(property="day", type="integer", example=21),
     *               ),
     *             ),
     *           ),
     *           @OA\Property(property="章程所訂外文公司名稱", type="string", example="OpenFun Company Limited"),
     *           @OA\Property(property="複數表決權特別股", type="string", example="無"),
     *           @OA\Property(property="對於特定事項具否決權特別股", type="string", example="無"),
     *           @OA\Property(property="每股金額(元)", type="integer", example=10),
     *           @OA\Property(property="已發行股份總數(股)", type="string", example="3,546,562,881"),
     *           @OA\Property(property="財政部", type="object",
     *             @OA\Property(property="營業人名稱", type="string", example="ＯＯ有限公司"),
     *             @OA\Property(property="資本額", type="string", example="1600000"),
     *             @OA\Property(property="設立日期", type="object",
     *               @OA\Property(property="year", type="integer", example=2012),
     *               @OA\Property(property="month", type="integer", example=12),
     *               @OA\Property(property="day", type="integer", example=21),
     *             ),
     *             @OA\Property(property="使用統一發票", type="string", example="Y"),
     *             @OA\Property(property="行業", type="array", @OA\Items(type="string"), example={"639099", "未分類其他資訊服務"}),
     *             @OA\Property(property="營業地址", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *             @OA\Property(property="負責人姓氏", type="string", example="王"),
     *             @OA\Property(property="總機構統一編號", type="string", example="89285269"),
     *             @OA\Property(property="組織別名稱", type="string", example="有限公司"),
     *           ),
     *           @OA\Property(property="統一編號", type="string", example="89285269"),
     *         ),
     *       ),
     *       @OA\Property(property="found", type="integer", example="1")
     *     ),
     *   ),
     * )
     */
    public function fundAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $search_ret = SearchLib::searchCompaniesByFund($_GET['q'], $page);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total->value;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    /**
     * @OA\Get(
     *   path="/api/name", summary="依姓名搜尋公司", description="依代表人、經理人、董監事、負責人的人名搜尋公司",
     *   @OA\Parameter(name="q", in="query", description="搜尋字串", required=true, @OA\Schema(type="string"), example="張國煒"),
     *   @OA\Parameter(name="page", in="query", description="頁數", required=false, @OA\Schema(type="integer"), example=1),
     *   @OA\Parameter(name="callback", in="query", description="callback function name", required=false, @OA\Schema(type="string")),
     *   @OA\Response( response="200", description="依姓名搜尋公司",
     *     @OA\JsonContent( type="object",
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(type="object", description="公司資料",
     *           @OA\Property(property="公司狀況", type="string", example="核准設立"),
     *           @OA\Property(property="公司名稱", type="string", example="ＯＯ有限公司"),
     *           @OA\Property(property="資本總額(元)", type="string", example="1,600,000"),
     *           @OA\Property(property="代表人姓名", type="string", example="王ＯＯ"),
     *           @OA\Property(property="公司所在地", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *           @OA\Property(property="登記機關", type="string", example="新北市政府"),
     *           @OA\Property(property="核准設立日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="最後核准變更日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="所營事業資料", type="array",
     *             @OA\Items(type="array", @OA\Items(type="string")),
     *             example={{"I301010", "資訊軟體服務業"}},
     *           ),
     *           @OA\Property(property="董監事名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="職稱", type="string", example="董事"),
     *               @OA\Property(property="姓名", type="string", example="王ＯＯ"),
     *               @OA\Property(property="所代表法人", type="array",
     *                 @OA\Items(type="string"),
     *                 example={"89285269（公司統編）", "ＯＯ有限公司"},
     *               ),
     *               @OA\Property(property="出資額", type="string", example="1,600,000"),
     *             ),
     *           ),
     *           @OA\Property(property="經理人名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="姓名", type="string", example="李ＯＯ"),
     *               @OA\Property(property="到職日期", type="object",
     *                 @OA\Property(property="year", type="integer", example=2012),
     *                 @OA\Property(property="month", type="integer", example=12),
     *                 @OA\Property(property="day", type="integer", example=21),
     *               ),
     *             ),
     *           ),
     *           @OA\Property(property="章程所訂外文公司名稱", type="string", example="OpenFun Company Limited"),
     *           @OA\Property(property="複數表決權特別股", type="string", example="無"),
     *           @OA\Property(property="對於特定事項具否決權特別股", type="string", example="無"),
     *           @OA\Property(property="每股金額(元)", type="integer", example=10),
     *           @OA\Property(property="已發行股份總數(股)", type="string", example="3,546,562,881"),
     *           @OA\Property(property="財政部", type="object",
     *             @OA\Property(property="營業人名稱", type="string", example="ＯＯ有限公司"),
     *             @OA\Property(property="資本額", type="string", example="1600000"),
     *             @OA\Property(property="設立日期", type="object",
     *               @OA\Property(property="year", type="integer", example=2012),
     *               @OA\Property(property="month", type="integer", example=12),
     *               @OA\Property(property="day", type="integer", example=21),
     *             ),
     *             @OA\Property(property="使用統一發票", type="string", example="Y"),
     *             @OA\Property(property="行業", type="array", @OA\Items(type="string"), example={"639099", "未分類其他資訊服務"}),
     *             @OA\Property(property="營業地址", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *             @OA\Property(property="負責人姓氏", type="string", example="王"),
     *             @OA\Property(property="總機構統一編號", type="string", example="89285269"),
     *             @OA\Property(property="組織別名稱", type="string", example="有限公司"),
     *           ),
     *           @OA\Property(property="統一編號", type="string", example="89285269"),
     *         ),
     *       ),
     *       @OA\Property(property="found", type="integer", example="1")
     *     ),
     *   ),
     * )
     */
    public function nameAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $search_ret = SearchLib::searchCompaniesByPerson($_GET['q'], $page);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total->value;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    /**
     * @OA\Get(
     *   path="/api/branch", summary="依母公司統編搜尋分公司", description="依母公司統編搜尋分公司",
     *   @OA\Parameter(name="q", in="query", description="搜尋字串", required=true, @OA\Schema(type="string"), example="11768704"),
     *   @OA\Parameter(name="page", in="query", description="頁數", required=false, @OA\Schema(type="integer"), example=1),
     *   @OA\Parameter(name="callback", in="query", description="callback function name", required=false, @OA\Schema(type="string")),
     *   @OA\Response( response="200", description="依統一編號搜尋分公司",
     *     @OA\JsonContent( type="object",
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(type="object", description="公司資料",
     *           @OA\Property(property="公司狀況", type="string", example="核准設立"),
     *           @OA\Property(property="公司名稱", type="string", example="ＯＯ有限公司"),
     *           @OA\Property(property="資本總額(元)", type="string", example="1,600,000"),
     *           @OA\Property(property="代表人姓名", type="string", example="王ＯＯ"),
     *           @OA\Property(property="公司所在地", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *           @OA\Property(property="登記機關", type="string", example="新北市政府"),
     *           @OA\Property(property="核准設立日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="最後核准變更日期", type="object",
     *             @OA\Property(property="year", type="integer", example=2012),
     *             @OA\Property(property="month", type="integer", example=12),
     *             @OA\Property(property="day", type="integer", example=21),
     *           ),
     *           @OA\Property(property="所營事業資料", type="array",
     *             @OA\Items(type="array", @OA\Items(type="string")),
     *             example={{"I301010", "資訊軟體服務業"}},
     *           ),
     *           @OA\Property(property="董監事名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="職稱", type="string", example="董事"),
     *               @OA\Property(property="姓名", type="string", example="王ＯＯ"),
     *               @OA\Property(property="所代表法人", type="array",
     *                 @OA\Items(type="string"),
     *                 example={"89285269（公司統編）", "ＯＯ有限公司"},
     *               ),
     *               @OA\Property(property="出資額", type="string", example="1,600,000"),
     *             ),
     *           ),
     *           @OA\Property(property="經理人名單", type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="序號", type="string", example="0001"),
     *               @OA\Property(property="姓名", type="string", example="李ＯＯ"),
     *               @OA\Property(property="到職日期", type="object",
     *                 @OA\Property(property="year", type="integer", example=2012),
     *                 @OA\Property(property="month", type="integer", example=12),
     *                 @OA\Property(property="day", type="integer", example=21),
     *               ),
     *             ),
     *           ),
     *           @OA\Property(property="章程所訂外文公司名稱", type="string", example="OpenFun Company Limited"),
     *           @OA\Property(property="複數表決權特別股", type="string", example="無"),
     *           @OA\Property(property="對於特定事項具否決權特別股", type="string", example="無"),
     *           @OA\Property(property="每股金額(元)", type="integer", example=10),
     *           @OA\Property(property="已發行股份總數(股)", type="string", example="3,546,562,881"),
     *           @OA\Property(property="財政部", type="object",
     *             @OA\Property(property="營業人名稱", type="string", example="ＯＯ有限公司"),
     *             @OA\Property(property="資本額", type="string", example="1600000"),
     *             @OA\Property(property="設立日期", type="object",
     *               @OA\Property(property="year", type="integer", example=2012),
     *               @OA\Property(property="month", type="integer", example=12),
     *               @OA\Property(property="day", type="integer", example=21),
     *             ),
     *             @OA\Property(property="使用統一發票", type="string", example="Y"),
     *             @OA\Property(property="行業", type="array", @OA\Items(type="string"), example={"639099", "未分類其他資訊服務"}),
     *             @OA\Property(property="營業地址", type="string", example="新北市板橋區ＯＯ路１２３號"),
     *             @OA\Property(property="負責人姓氏", type="string", example="王"),
     *             @OA\Property(property="總機構統一編號", type="string", example="89285269"),
     *             @OA\Property(property="組織別名稱", type="string", example="有限公司"),
     *           ),
     *           @OA\Property(property="統一編號", type="string", example="89285269"),
     *         ),
     *       ),
     *       @OA\Property(property="found", type="integer", example="1")
     *     ),
     *   ),
     * )
     */
    public function branchAction()
    {
        $page = intval($_GET['page']) ?: 1;
        $search_ret = SearchLib::searchCompaniesByParent($_GET['q'], $page);
        $ret = new StdClass;
        $data = array();
        foreach ($search_ret->hits->hits as $hit) {
            $source = Unit::find($hit->_id)->getData();
            $source->{'統一編號'} = $hit->_id;
            $data[] = $source;
        }

        $ret->data = $data;
        $ret->found = $search_ret->hits->total->value;
        return $this->jsonp($ret, strval($_GET['callback']));
    }

    /**
     * @OA\Get(
     *   path="/api/bulksearch", summary="批量依公司名稱查詢統編", description="批量依公司名稱查詢統編",
     *   @OA\Parameter(name="names", in="query", description="公司名稱（以逗號分隔）", required=true,
     *     @OA\Schema(type="string"), example="中華航空股份有限公司,星宇航空股份有限公司"),
     *   @OA\Parameter(name="callback", in="query", description="callback function name", required=false, @OA\Schema(type="string")),
     *   @OA\Response( response="200", description="批量依公司名稱查詢統編",
     *     @OA\JsonContent( type="array",
     *       example={{"query": "中華航空股份有限公司", "result": "59566000"}, {"query": "星宇航空股份有限公司", "result": "82249999"}},
     *       @OA\Items(type="object", description="個別查詢結果",
     *         @OA\Property(property="query", type="string"),
     *         @OA\Property(property="result", type="string"),
     *       ),
     *     ),
     *   ),
     * )
     */
    public function bulksearchAction()
    {
        $ret = SearchLib::bulkSearchCompany(array(
            'name' => explode(',', $_REQUEST['names']),
        ));
        return $this->jsonp($ret, strval($_GET['callback']));
    }
}
