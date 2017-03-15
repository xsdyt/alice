<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
// use App\Models\UploadModel;
// use App\Helpers\ZipHelper;


class UploadController extends Controller
{
	public function anyUploadResource(){
        //获取配置
        $id = Input::get('id','0');
        $index = Input::get('index','0');
        $type=Input::get('type','1');//1主播小图2大厅拍卖主播图片路径3产品详细信息图片路径4产品图片5主播详细信息图片路径6大厅红与黑主播图片路径7产品logo 8首页公告 9消息标题 10消息内容 11礼物
        $file1=Input::file('file1',true);//图片
// echo "<pre>";
                   
//                    print_r($file1);
//                    echo "</pre>";exit;
//                    $file1 格式
//                    Symfony\Component\HttpFoundation\File\UploadedFile Object
//                     (
//                            [test:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 
//                          文件名字  [originalName:Symfony\Component\HttpFoundation\File\UploadedFile:private] => Jellyfish.jpg
//                          文件属性 [mimeType:Symfony\Component\HttpFoundation\File\UploadedFile:private] => image/jpeg
//                          文件大小  [size:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 775702
//                            [error:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 0
//                           文件tmp路经 [pathName:SplFileInfo:private] => C:\Windows\php79EA.tmp
//                           文件tmp名字 [fileName:SplFileInfo:private] => php79EA.tmp
//                         文件后缀名  getClientOriginalExtension()
//                        )
                    
        if($file1!=1)
        { //判断是否选择文件
            $clientname=$file1->getClientOriginalName();//获取文件名字
            $entension=$file1->getClientOriginalExtension();//获取文件后缀名
            //echo $entension;exit;
            $allowtype=array("png",'jpeg','jpg');
            if(!in_array($entension,$allowtype)){
                echo "<script type='text/javascript'>alert('这是不允许的类型');history.back();</script>";
                exit;
            }
            if($type=="1")
            {
               $path_toux=config('game.catalog-dealer-small');
               $file1->move($path_toux,"$id.png");
               //return redirect(Config('game.alice-bakebd-prefix')."auction/query");
               return redirect(Config('game.alice-bakebd-prefix')."dealer/dealers-paging");
            }
            else if($type=="2")
            {
               $path_tup=config('game.catalog-dealer-rooms-auction');
               // if (!file_exists($path_tup)) {  
               //      mkdir($path_tup,0644,true);
               //      chmod($path_tup,0777);
               //  }
               $file1->move($path_tup,"$id.png");
               return redirect(Config('game.alice-bakebd-prefix')."auction/auction-manage");
                // return redirect(Config('game.alice-bakebd-prefix')."auction/query");
            }
            else if($type=="3")
            {
                $path_image=config('game.catalog-products-detail');
                $path_image=$path_image.$id.'/';
                $file1->move($path_image,"$index.png");
                return redirect(Config('game.alice-bakebd-prefix')."products/product-classify-manage");
                 // return redirect(Config('game.alice-bakebd-prefix')."auction/query");
            }
            else if($type=="4")
            {
                $path_image=config('game.catalog-products-product');
                $path_image=$path_image.$id.'/';
                $file1->move($path_image,"$index.png");
                 // return redirect(Config('game.alice-bakebd-prefix')."auction/query");
                return redirect(Config('game.alice-bakebd-prefix')."products/products-manage");
            }
            else if($type=="5")
            {
                $path_image=config('game.catalog-dealer-detail');
                $path_image=$path_image.$id.'/';
                $file1->move($path_image,"$index.png");
                 // return redirect(Config('game.alice-bakebd-prefix')."auction/query");
                return redirect(Config('game.alice-bakebd-prefix')."dealer/dealers-paging");
            }
            else if($type=="6")
            {
                $path_tup=config('game.catalog-dealer-rooms-pokerrb');
                $file1->move($path_tup,"$id.png");
                 // return redirect(Config('game.alice-bakebd-prefix')."auction/query");
                return redirect(Config('game.alice-bakebd-prefix')."pokerrb/poker-rb-manage");
            }
            else if($type=="7")
            {
                $path_tup=config('game.catalog-products-logo');
                $file1->move($path_tup,"$id.png");
                return redirect(Config('game.alice-bakebd-prefix')."/products/product-classify-manage");
            }else if($type=="8")
            {
                $path_tup=config('game.catalog-home-notice');
                $file1->move($path_tup,"$id.png");
                return redirect(Config('game.alice-bakebd-prefix')."/home/notice");
            }else if($type=="9")
            {
                $path_tup=config('game.catalog-home-news-title');
                $file1->move($path_tup,"$id.png");
                return redirect(Config('game.alice-bakebd-prefix')."/home/news");
            }else if($type=="10")
            {
                $path_tup=config('game.catalog-home-news-content');
                $file1->move($path_tup,"$id.png");
                return redirect(Config('game.alice-bakebd-prefix')."/home/news");
            }else if($type=="11")
            {
                $path_tup=config('game.catalog-config-gift');
                $file1->move($path_tup,"$id.png");
                return redirect(Config('game.alice-bakebd-prefix')."/config/gift");
            }
            
        }else{
           echo "<script>alert('请选择文件');history.go(-1);</script>";
        }
                      
    }

    function anyReadFile()
    {
        $products=Input::get('products','');
        $products=json_decode($products);
        foreach ($products as $productsimage){
            $id = $productsimage->id;
            $path_image=config('game.catalog-products-product').$id.'/';
           // $images_name_array = (object)$this->readfiles($path_image1);
            //$productsimage->images=$images_name_array;
            
              $file_path_image = $path_image.$id.'.png';
              
              $flag_image= file_exists($file_path_image);
              
              for($i=1;$i<=6;$i++)
              {
                  $data["pic"][$id][$i]=file_exists($path_image.$i.'.png');
                  $data["path"][$id][$i]=$path_image.$id.'.png';
              }
              
            if($flag_image){
              //所有的图片显示在一起的
              //首先获取到id所在的所有文件名
              $arr_images = $this->readfiles($path_image);
              $images_name_array = (object)$arr_images;
              $productsimage->images=$images_name_array;
              
              //判断每个商品的图片是否满6张了，如果没满，就继续上传，页面中提供上传链接。
              $images_num = count($arr_images);
              if($images_num<6){
                  $productsimage->images_num=0;
              }
              else{
                  $productsimage->images_num=1;
              }
            }else
            {
               $productsimage->images='';
               $productsimage->images_num=1;
            }
        }
        return json_encode($data);
    }
   /*
   * 玩家修改头像
   */
    public function  anyUpdateHeader()
    {
        //獲取配置
        $https=config('game.https');
        
        $catelog=config('game.catalog-header');
        $uid=Input::get("uid",'244166');
      
          $file1=Input::file('photo',true);//图片
//        echo "<pre>";
//            print_r($file1);
//        echo "</pre>";
        $clientname=$file1->getClientOriginalName();//获取文件名字
        $entension=$file1->getClientOriginalExtension();//获取文件后缀名
        $name=time().".".$entension;
        $file1 -> move("./".$catelog,$name);
        $paths=$https.$_SERVER['HTTP_HOST'].$catelog.$name;
       // echo $paths;
        $photo=UploadModel::UpdateHeader($uid,$paths);
        $result=json_encode($photo,JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
        $response = Response::make($result, 200);
        $response->header('Content-Type','text/html');
        return $response;
       
    }
     //删除资源文件
    public function anyDeleteFile(){
        $file_url=Input::get('imgurl',true);//图片名
        $catelog=config('game.catalog-file');
        $https=config('game.https');
        $path=str_replace($https.$_SERVER['HTTP_HOST'].'/',"",$file_url);
        $result = @unlink ($path);
        if ($result == false) {
        echo '1';
        } else {
        echo '0';
        }
       
    }
    //获取资源zip版本号
    public function anyGetZipVersion(){
      $config_id=Config::get('game.config-id');
      $info=UploadModel::GetZipVersion($config_id);
      return $info;
    }  
}
