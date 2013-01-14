<?php

/**
 * Pix_Array_Volumable
 *
 * @package Array
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
interface Pix_Array_Volumable
{
    /**
     * after 指定要從哪一筆開始(不包含 $id 這筆)，這個 $id 可以是 getVolumePos 回傳的值
     *
     * @param mixed $id 會跟 Pix_Array_Volumable->getVolumePos() 回傳的值同格式。
     * @access public
     * @return Pix_Array_Volumable 回傳自己
     */
    public function after($id);

    /**
     * limit 指定一次要幾筆
     *
     * @param int $limit
     * @access public
     * @return Pix_Array_Volumable 回傳自己
     */
    public function limit($limit = 10);

    /**
     * rewind 回傳符合 ->after($after)->limit($limit) 條件的 Iterator
     *
     * @access public
     * @return Iteratorable 可以直接丟一個 size 是 $limit 的 array 進來。
     */
    public function rewind();


    /**
     * getVolumePos 回傳 $row 這個 row 在 Pix_Array_Volumable 的位置。
     *
     * @param mixed $row 這個類型要跟 rewind() 回傳的 Iterator 的 value 一樣類型
     * @access public
     * @return mixed 這個類型要跟 ->after($id) 的 $id 一樣，到時候會噴給他用
     */
    public function getVolumePos($row);

    /**
     * getVolumeID 取得這個 Volume 的代表 ID ，用在 cache 結果用的。
     *
     * @access public
     * @return string|null 若為 null 的話，就不會使用 cache
     */
    public function getVolumeID();
}
