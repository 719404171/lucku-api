<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/9
 * Time: 13:52
 */

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderDetails;
use App\Models\OrderLogistics;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    protected $Order;
    protected $OrderDetails;
    protected $OrderLogistics;
    protected $OrderComment;

    public function __construct()
    {
        $this->Order = new Order();
        $this->OrderDetails = new OrderDetails();
        $this->OrderLogistics = new OrderLogistics();
        $this->OrderComment = new OrderComment();
        $this->language = app('global_language') == "en" ? "en_" : "";//新增
    }

    /**
     * 獲取訂單列表
     * @param Request $request
     * @return array
     */
    public function getOrderListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Order->getOrderListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

//    /**
//     * 獲取訂單銷售額
//     * @param Request $request
//     * @return array
//     */
//    public function getGoodsSaleWeb(Request $request){
//        $data = $request->post();
//        $datas = $this->Order->getGoodsSaleWeb($data);
//        return $this->success(__('web.success'),$datas);
//    }

    /**
     * 添加訂單物流
     * @param Request $request
     * @return array
     */
    public function addOrderLogisticsWeb(Request $request){
        $data = $request->post();
        $datas = $this->OrderLogistics->addOrderLogisticsWeb($data);
        return $datas;
    }

    /**
     * 修改预约时间
     * @param Request $request
     * @return array
     */
    public function editOrderTimeWeb(Request $request){
        $token = $request->header('TOKEN');
        $data = $request->post();
        $datas = $this->Order->editOrderTimeWeb($data,$token);
        return $datas;
    }

    /**
     * 修改订单支付时间(修改订单支付時間/与订单详情同步)后缀?order=true
     * @param Request $request
     * @return array
     */
    public function editPaymentTime(Request $request){
        $datas = $request->post();
        $data = $this->Order->editPaymentTime($datas);
        return $data;
    }

    /**
     * 确认完成/確認收貨
     * @param Request $request
     * @return array
     */
    public function confirmReceiptWeb(Request $request){
        $token = $request->header('TOKEN');
        $data = $request->post();
        $datas = $this->OrderDetails->confirmReceiptWeb($data,$token);
        return $datas;
    }

    /**
     * 确认支付
     * @param Request $request
     * @return array
     */
    public function confirmPaymentWeb(Request $request){
        $token = $request->header('TOKEN');
        $orderID = $request->post('order_id');
        $data = $this->Order->confirmPaymentWeb($orderID,$token);
        return $data;
    }

    /**
     * Summary of ConfirmBooking
     * @param Request $request
     * @return array
     */
    public function scanConfirmOrderWeb(Request $request){
        if(empty($request->post('order_no'))){
            return $this->parametersError(__('api.param_error'));
        }else{
            $data = $request->post();
            $token = $request->header('token');
            $results = $this->Order->scanConfirmOrderWeb($data,$token);
            return $results;
        }
    }

    /**
     * 刪除訂單
     * @param Request $request
     * @return array
     */
    public function deleteOrderWeb(Request $request){
        $token = $request->header('TOKEN');
        $orderID = $request->post('order_id');
        $data = $this->Order->deleteOrderWeb($orderID,$token);
        return $data;
    }

    /**
     * 发票/收據下载
     * @param Request $request
     */
    public function generateInvoiceWeb(Request $request){
        $OrderId = $request->get('order_id');
        $language = $request->get('language');
        if(!empty($OrderId)){
            $ShopInfo=ShopSetting::where('shop_status',1)->select('*')->first();
            $shop_address=$ShopInfo['shop_address'];
            $shop_name=$ShopInfo['shop_name'];
            OrderDetails::$modify = true;
            $OrderInfo = Order::with('OrderDetails')
                ->where('order_id',$OrderId)
                ->select('*')
                ->first()->toArray();
            $PDF['company']=empty($shop_name) ?  config('services.basics.base_name') : $shop_name;//公司名稱
            $PDF['Subject']=empty($shop_address) ? config('services.basics.base_address'):$shop_address;//地址
            $PDF['logo']=self::uploadAvatar();//LOGO
            if(!empty($OrderInfo)){
                if($OrderInfo['payment_status']==1 || $OrderInfo['payment_status']== 2 || $OrderInfo['payment_status']== 4){
                    $PDF['title']=$PDF['company']." Invoice";//PDF標題-發票
                    if($language == "en"){
                        $PDF['pdf_name'] = " Invoice";
                        $PDF['Time']='Invoice Date：'.date('Y-m-d H:i:s');//發票日期
                    }else {
                        $PDF['pdf_name'] = " 發票";//PDF標題-發票
                        $PDF['Time']='發票日期：'.date('Y-m-d H:i:s');//發票日期
                    }
                }else{
                    $PDF['title']=$PDF['company']." Receipt";//PDF標題-收據
                    if($language == "en"){
                        $PDF['pdf_name'] = " Receipt";
                        $PDF['Time']='Payment Date：'.$OrderInfo['payment_at'];//發票日期
                    }else{
                        $PDF['pdf_name']=" 收據";//PDF標題-收據
                        $PDF['Time']='付款日期：'.$OrderInfo['payment_at'];//發票日期
                    }
                }
                if($language == "en") {
                    foreach ($OrderInfo['order_details'] as &$detail) {
                        $detail['en_product_name'] = $detail['product']['en_product_name'] ?? $detail['product']['product_name'];
                    }
                    unset($detail);
                    self::CreateENPDF($PDF, $OrderInfo);
                }else{
                    self::CreatePDF($PDF, $OrderInfo);
                }
            }
        }
    }

    /**
     * 創建發票/收據PDF
     * @param $PDF
     * @param $OrderInfo
     */
    public static function CreatePDF($PDF,$OrderInfo){
        $pdf = new \TCPDF();
        // 设置文档信息
        $pdf->SetCreator($PDF['company']);
        $pdf->SetAuthor($PDF['company']);
        $pdf->SetTitle($PDF['title']);
        $pdf->SetSubject($PDF['Subject']);
        $pdf->SetKeywords('TCPDF, PDF, PHP');
        // 设置页眉和页脚信息
        $pdf->SetHeaderData($PDF['logo'], 10, $PDF['title'], $PDF['Subject'], [80, 80, 80], [0, 0, 0]);
        $pdf->setFooterData([0, 64, 0], [0, 64, 128]);
        // 设置页眉和页脚字体
        $pdf->setHeaderFont(['stsongstdlight', '', '10']);
        $pdf->setFooterFont(['helvetica', '', '8']);
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');
        // 设置间距
        $pdf->SetMargins(15, 15, 15);//页面间隔
        $pdf->SetHeaderMargin(5);//页眉top间隔
        $pdf->SetFooterMargin(10);//页脚bottom间隔
        // 设置分页
        $pdf->SetAutoPageBreak(true, 25);
        // 设置自动换页
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // 设置图像比例因子
        $pdf->setImageScale(1.25);
        // 设置默认字体构造子集模式
        $pdf->setFontSubsetting(true);
        // 设置字体 stsongstdlight支持中文
        $pdf->SetFont('stsongstdlight', '', 14);
        //第一页
        $pdf->AddPage();
        $html[0]='<div style="text-align: center;letter-spacing:1px"><h1>'.$PDF['title'].' NO. '.$OrderInfo['order_id'].'</h1></div>';
        $html[1]='<p style="letter-spacing:1px">訂單編號：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_no'].'</span></p>';
        $html[2]='<p style="letter-spacing:1px"><span style="color: #515256;font-size: 17px">'.$PDF['Time'].'</span></p>';
        $html[3]='<p style="letter-spacing:1px">收貨姓名：<span style="color: #515256;font-size: 15px">'.$OrderInfo['delivery_name'].'</span></p>';
        $html[4]='<p style="letter-spacing:1px">收貨電話：<span style="color: #515256;font-size: 15px">'.$OrderInfo['delivery_phone'].'</span></p>';
        $html[5]='<p style="letter-spacing:1px">收貨地址：<span style="color: #515256;font-size: 15px">'.$OrderInfo['delivery_address'].'</span></p>';
        if(!empty($OrderInfo['order_details'])){
           for($i=0;$i<count($OrderInfo['order_details']);$i++) {
               $OrderInfo['order_details'][$i]['product_specs'] = empty($OrderInfo['order_details'][$i]['product_specs']) ? '-' : $OrderInfo['order_details'][$i]['product_specs'];
               $DetailsHeader ='<table border="1" cellspacing="0" cellpadding="3">
               <tr>
                    <th colspan="3"  align="left">購買詳情：</th>
                </tr>
                <tr>
                    <td style="letter-spacing:1px">詳情ID：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['details_id'].'</span></td>
                    <td style="letter-spacing:1px">商品名稱：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['product_name'].'</span></td>
                    <td style="letter-spacing:1px">商品規格：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['product_specs'].'</span></td>
                </tr>
                <tr>
                    <td style="letter-spacing:1px">商品數量：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['product_number'].'</span></td>
                    <td style="letter-spacing:1px">單價：<span style="color: #515256;font-size: 15px">HK $'.$OrderInfo['order_details'][$i]['product_price'].'</span></td>
                    <td style="letter-spacing:1px">小計：<span style="color: #515256;font-size: 15px">HK $'.$OrderInfo['order_details'][$i]['product_money'].'</span></td>
                </tr></table>';
               $html[(6 + $i)] = $DetailsHeader;
            }
        }

        $html[count($html) + 1]='<p style="letter-spacing:1px">備註：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_remark'].'</span></p>';
        $html[count($html) + 2]='<p style="letter-spacing:1px">總費用：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_money'].'</span></p>';

        foreach ($html as $key => $value) {
            $pdf->writeHTML($html[$key], true, false, true, false, '');
            $pdf->Ln(3);//换行符
        }
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="letter-spacing:1px">如果您對本'.$PDF['pdf_name'].'有任何問題，請使用下列聯絡資訊：</p>', true, false, true, false, '');
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="letter-spacing:1px">於 '.$PDF['company'].' APP 專頁 聯絡我們！</p>', true, false, true, false, '');
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="letter-spacing:1px">感謝您的惠顧！</p>', true, false, true, false, '');
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="text-align:right">時間：'.date('Y-m-d H:i:s').'</p>', true, false, true, false, '');
        //输出PDF
        $pdf->Output($OrderInfo['order_no'].'.pdf', 'D');//I输出、D下载
    }

    /**
     * 創建英文發票/收據PDF
     * @param $PDF
     * @param $OrderInfo
     */
    public static function CreateENPDF($PDF,$OrderInfo){
        $pdf = new \TCPDF();
        // 设置文档信息
        $pdf->SetCreator($PDF['company']);
        $pdf->SetAuthor($PDF['company']);
        $pdf->SetTitle($PDF['title']);
        $pdf->SetSubject($PDF['Subject']);
        $pdf->SetKeywords('TCPDF, PDF, PHP');
        // 设置页眉和页脚信息
        $pdf->SetHeaderData($PDF['logo'], 10, $PDF['title'], $PDF['Subject'], [80, 80, 80], [0, 0, 0]);
        $pdf->setFooterData([0, 64, 0], [0, 64, 128]);
        // 设置页眉和页脚字体
        $pdf->setHeaderFont(['stsongstdlight', '', '10']);
        $pdf->setFooterFont(['helvetica', '', '8']);
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');
        // 设置间距
        $pdf->SetMargins(15, 15, 15);//页面间隔
        $pdf->SetHeaderMargin(5);//页眉top间隔
        $pdf->SetFooterMargin(10);//页脚bottom间隔
        // 设置分页
        $pdf->SetAutoPageBreak(true, 25);
        // 设置自动换页
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // 设置图像比例因子
        $pdf->setImageScale(1.25);
        // 设置默认字体构造子集模式
        $pdf->setFontSubsetting(true);
        // 设置字体 stsongstdlight支持中文
        $pdf->SetFont('stsongstdlight', '', 14);
        //第一页
        $pdf->AddPage();
        $html[0]='<div style="text-align: center;letter-spacing:1px"><h1>'.$PDF['title'].' NO. '.$OrderInfo['order_id'].'</h1></div>';
        $html[1]='<p style="letter-spacing:1px">Order Number：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_no'].'</span></p>';
        $html[2]='<p style="letter-spacing:1px"><span style="color: #515256;font-size: 17px">'.$PDF['Time'].'</span></p>';
        $html[3]='<p style="letter-spacing:1px">Name Of Recipient：<span style="color: #515256;font-size: 15px">'.$OrderInfo['delivery_name'].'</span></p>';
        $html[4]='<p style="letter-spacing:1px">Delivery Phone：<span style="color: #515256;font-size: 15px">'.$OrderInfo['delivery_phone'].'</span></p>';
        $html[5]='<p style="letter-spacing:1px">Receiving Address：<span style="color: #515256;font-size: 15px">'.$OrderInfo['delivery_address'].'</span></p>';
        if(!empty($OrderInfo['order_details'])){
            for($i=0;$i<count($OrderInfo['order_details']);$i++) {
                $OrderInfo['order_details'][$i]['product_specs'] = empty($OrderInfo['order_details'][$i]['product_specs']) ? '-' : $OrderInfo['order_details'][$i]['product_specs'];
                $DetailsHeader ='<table border="1" cellspacing="0" cellpadding="3">
               <tr>
                    <th colspan="3"  align="left">Purchase Details：</th>
                </tr>
                <tr>
                    <td style="letter-spacing:1px">Details ID：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['details_id'].'</span></td>
                    <td style="letter-spacing:1px">Product Name：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['en_product_name'].'</span></td>
                    <td style="letter-spacing:1px">Product Spec：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['product_specs'].'</span></td>
                </tr>
                <tr>
                    <td style="letter-spacing:1px">Product Quantity：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_details'][$i]['product_number'].'</span></td>
                    <td style="letter-spacing:1px">Unit Price：<span style="color: #515256;font-size: 15px">HK $'.$OrderInfo['order_details'][$i]['product_price'].'</span></td>
                    <td style="letter-spacing:1px">Subtotal：<span style="color: #515256;font-size: 15px">HK $'.$OrderInfo['order_details'][$i]['product_money'].'</span></td>
                </tr></table>';
                $html[(6 + $i)] = $DetailsHeader;
            }
        }

        $html[count($html) + 1]='<p style="letter-spacing:1px">Notes：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_remark'].'</span></p>';
        $html[count($html) + 2]='<p style="letter-spacing:1px">Total Cost：<span style="color: #515256;font-size: 15px">'.$OrderInfo['order_money'].'</span></p>';

        foreach ($html as $key => $value) {
            $pdf->writeHTML($html[$key], true, false, true, false, '');
            $pdf->Ln(3);//换行符
        }
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="letter-spacing:1px">If you have any questions about this '.$PDF['pdf_name'].', please use the following contact information：</p>', true, false, true, false, '');
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="letter-spacing:1px">A surname '.$PDF['company'].' APP Contact us on the dedicated page！</p>', true, false, true, false, '');
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="letter-spacing:1px">Thank you for your patronage！</p>', true, false, true, false, '');
        $pdf->Ln(3);//换行符
        $pdf->writeHTML('<p style="text-align:right">Time：'.date('Y-m-d H:i:s').'</p>', true, false, true, false, '');
        //输出PDF
        $pdf->Output($OrderInfo['order_no'].'.pdf', 'D');//I输出、D下载
    }

    /**
     * 保存图片到本地
     * @return string
     */
    public static function uploadAvatar(){
        try {
            $ShopInfo=ShopSetting::where('shop_status',1)->select('shop_logo')->first()->shop_logo;
            $imageUrl= empty($ShopInfo) ? config('services.basics.base_logo'):$ShopInfo;//圖片地址
            $name= config('services.basics.base_name').'.png';//图片名称
            $exists=Storage::exists('public/'.$name);//图片是否存在
            if ($exists) {
                return  '../../../../../storage/app/public/' . $name;
            }
            // 使用file_get_contents读取图片内容
            $imageData = file_get_contents($imageUrl);
            // 转换成base64编码的字符串
            $imgBase64Code = "data:image/jpeg;base64," . base64_encode($imageData);
            if (!empty($imgBase64Code)) {
                // 将文件保存到默认的磁盘，并指定新的文件名
                $info = Storage::putFileAs('public', $imgBase64Code, $name);
                if ($info) {
                    return  '../../../../../storage/app/public/' . $name;
                }
            }else{
                return '../../../../../logo.png';
            }

        } catch (\Exception $e) {
            return $e;
        }
    }

    /**
     * 獲取評論列表
     * @param Request $request
     * @return array
     */
    public function getOrderCommentListWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->OrderComment->getOrderCommentListWeb($data,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 刪除評論
     * @param Request $request
     * @return array
     */
    public function deleteOrderCommentWeb(Request $request){
        $commentID = $request->post('comment_id');
        $data = $this->OrderComment->deleteOrderCommentWeb($commentID);
        return $data;
    }

    /**
     * 獲取日曆訂單
     * @param Request $request
     * @return array
     */
    public function getCalendarOrdersWeb(Request $request){
        $data = $request->post();
        $datas = $this->Order->getCalendarOrdersWeb($data);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 添加線下訂單
     * @param Request $request
     * @return array
     */
    public function addOfflineOrdersWeb(Request $request){
        $data = $request->post();
        $token = $request->header('TOKEN');
        $datas = $this->Order->addOfflineOrdersWeb($data,$token);
        return $datas;
    }

    /**
     * web創建訂單
     * @param Request $request
     * @return array
     */
    public function WebAddOrder(Request $request){
        $data = $request->post();
        $datas = $this->Order->WebAddOrder($data);
        return $datas;
    }

    /**
     * 獲取訂單分析
     * @param Request $request
     * @return array
     */
    public function getOrderAnalysisWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Order->getOrderAnalysisWeb($data,$language);
        return $datas;
    }

    /**
     * 獲取銷售方向分析
     * @param Request $request
     * @return mixed
     */
    public function getSalesDirectionWeb(Request $request){
        $data = $request->post();
        $language = self::getLanguageWeb();
        $datas = $this->Order->getSalesDirectionWeb($data,$language);
        return $datas;
    }

}
