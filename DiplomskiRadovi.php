<?php
//Include html dom parser
include_once("simple_html_dom.php");
interface iRadovi {
    public function create($redni_broj);
    public function read();
    public function save();  
}
class DiplomskiRadovi implements iRadovi {
    var $naziv_rada;
    var $tekst_rada;
    var $link_rada;
    var $oib_tvrtke;
    
    var $radoviArray = [];
    
    function create($redni_broj){
        //Check condition
        if($redni_broj >= 2 && $redni_broj <= 6){
            //Initialize cUrl
            $curl = curl_init("https://stup.ferit.hr/index.php/zavrsni-radovi/page/$redni_broj/");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $html_string = curl_exec($curl); 
            curl_close($curl);

            //Call function which returns all html scrapped data
            $this->radoviArray = DiplomskiRadovi::GetAllWorks($html_string);
            return $this->radoviArray;
        }
    }
    
    function read(){
        try{
            //Connect to database
            $pdo = new PDO('mysql:dbname=radovi;host=localhost', 'root', '');
            //Create a query
            $q = 'SELECT * FROM diplomski_radovi';
            $r = $pdo->query($q);

            // Set to Fetch Mode
            $r->setFetchMode(PDO::FETCH_NUM);

            //Map results into an array
            $radoviArray = [];
            while ($row = $r->fetch()) {
                $radoviArray[] = [
                    'oib_tvrtke' => $row[1],
                    'link_rada' => $row[2],
                    'naziv_rada' => $row[3],
                    'tekst_rada' => $row[4],
                ];
            }
            unset($pdo);
            return $radoviArray;
        }catch (PDOException $e) {
            echo '<p>Dogodila se iznimka: ' . $e->getMessage() . '</p>';
        }
    }
    function save(){
        try {
            //Connect to database
            $pdo = new PDO('mysql:dbname=radovi;host=localhost', 'root', '');
            //Create a statement
            $stmt = $pdo->prepare('INSERT INTO diplomski_radovi (oib_tvrtke, link_rada, naziv_rada, tekst_rada)
                                VALUES (:oib_tvrtke, :link_rada, :naziv_rada, :tekst_rada)');
            
            //Insert every array element into Database
            $pdo->beginTransaction();
            foreach($this->radoviArray as $rad){
                $stmt->execute($rad);   
            }
            $pdo->commit();
            unset($pdo);
        } catch (PDOException $e) {
            echo '<p>Dogodila se iznimka: ' . $e->getMessage() . '</p>';
        }
    }

    function GetAllWorks($html_string){
        $radoviArray = [];
        $html = str_get_html($html_string);
        //Get all article information
        foreach($html->find('article') as $work) {
            $article = $work->find('a[class=fusion-rollover-link]', 0);
            $naziv_rada = $article->innertext;
            $link_rada = $article->href;

            $oib_tvrtke = $work->find('img', 0)->src;
            //Parse image string into company OIB
            $oib_tvrtke = str_replace(array('https://stup.ferit.hr/wp-content/logos/','.png'),'',$oib_tvrtke);

            $tekst_rada = $this->GetWorkContent($link_rada);

            //Add work data into Array
            $radoviArray[] = [
                'naziv_rada' => $naziv_rada,
                'tekst_rada' => $tekst_rada,
                'link_rada' => $link_rada,
                'oib_tvrtke' => $oib_tvrtke
            ];
        }
        return $radoviArray;
    }

    function GetWorkContent($link_rada){
        $curl = curl_init($link_rada);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $html = str_get_html(curl_exec($curl));
        curl_close($curl);

        $tekst_rada = "";
        //Find only content filled elements
        foreach($html->find('div[class=post-content] p') as $content){
            if($content->find('img', 0) == null){
                $tekst_rada .= $content->innertext . ' ';
            }
        }
        //Remove html tags from string
        return strip_tags($tekst_rada);
    }

    function ParseToHtml($data){
        if($data == null) return;
        foreach($data as $work){
            echo 
            '<div>
                <h2>'.$work['naziv_rada'].'</h2>
                <h3>'.$work['oib_tvrtke'].'</h3>
                <p>'.$work['tekst_rada'].'</p>
                <p>'.$work['link_rada'].'</p>
                <br>
            </div>';   
        }
    }
}
?>