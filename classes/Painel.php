<?php

    class Painel{

        public static $cargos = [0 => 'espectador', 1 => 'Sub-administrador', 2 => 'Administrador'];

        public static function logado(){
            return isset($_SESSION['login']) ? true : false;
        }

        public static function loggout(){
            setcookie('lembrar', 'true', time()-1, '/'); // destruir cookie time()-1
            session_destroy();
            header('Location: ' . INCLUDE_PATH_PAINEL);
        }

        public static function loadJS($files, $page){
            $array = explode('/',@$_GET['url']);
            if(count($array) == 1){
                $url = explode('/',@$_GET['url'])[0];
                if($page == $url){
                    foreach ($files as $key => $value) {
                        echo '<script src="'.INCLUDE_PATH.'js/'.$value.'"></script>';
                    }
                }
            }else if(count($array) == 2){
                $url = explode('/',@$_GET['url'])[1];
                if($page == $url){
                    foreach ($files as $key => $value) {
                        echo '<script src="'.INCLUDE_PATH.'js/'.$value.'"></script>';
                    }
                }
            }
        }

        public static function loadJSPainel($files, $page){
            $url = explode('/',@$_GET['url'])[0];
            if($page == $url){
                foreach ($files as $key => $value) {
                    echo '<script src="'.INCLUDE_PATH_PAINEL.'js/'.$value.'"></script>';
                }
            }
        }

        public static function loadPage(){
            if (isset($_GET['url'])) {
                $url = explode('/', $_GET['url']);
                if (file_exists('pages/' . $url[0] . '.php')) {
                    include('pages/' . $url[0] . '.php');
                }else {
                    if(Router::get('visualizar-info-concessionaria/?',function($par){
						include('views/visualizar-info-concessionaria.php');
					})){
                    }else if(Router::post('visualizar-info-concessionaria/?',function($par){
						include('views/visualizar-info-concessionaria.php');
					})){
                    }else if(Router::get('visualizar-info-venda/?',function($par){
						include('views/visualizar-info-venda.php');
                    })){
                    }else if(Router::post('visualizar-info-venda/?',function($par){
						include('views/visualizar-info-venda.php');
					})){
					}else{
						header('Location: '.INCLUDE_PATH_PAINEL);
					}
                }
            }else {
                //URL n existe = home.
                include('pages/home.php');
            }
        }

        public static function listarUsuariosOnline()
        {
            self::limparUsuariosOnline();
            $sql = MySql::conectar()->prepare("SELECT * FROM `tb_admin.online`");
            $sql->execute();
            return $sql->fetchAll();
        }

        public static function limparUsuariosOnline()
        {
            $date = date("Y-m-d H-i-s");
            $sql = MySql::conectar()->exec("DELETE FROM `tb_admin.online` WHERE ultima_acao < '$date' - INTERVAL 1 MINUTE");
        }

        public static function getVisitasTotais(){
            $sql = MySql::conectar()->prepare("SELECT * FROM `tb_admin.visitas`");
            $sql->execute();
            return $sql;
        }

        public static function getVisitasHoje(){
            $sql = MySql::conectar()->prepare("SELECT * FROM `tb_admin.visitas` WHERE dia = ?");
            $sql->execute(array(date('Y-m-d')));
            return $sql;
        }

        public static function convertDate($date){
            $array = explode('-', $date);
            $newDate = $array[2].'/'.$array[1].'/'.$array[0];
            return $newDate;
        }

        public static function formatarMoedaBD($val){
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
            return $val;
        }

        public static function convertMoney($val){
            return number_format($val, 2, ',', '.');
        }

        public static function convertKm($val){
            return number_format($val, 0, null, '.');
        }

        public static function alert($tipo, $mensagem){
            if($tipo == 'sucesso'){
                echo 
                '<div class="box-alert sucesso">
                    <i class="fa-solid fa-circle-check"></i> '.$mensagem.
                '</div>';
            }else if($tipo == 'erro'){
                echo 
                '<div class="box-alert erro">
                    <i class="fa-solid fa-circle-xmark"></i>'.$mensagem.
                '</div>';
            }
        }

        public static function alertJS($msg){
            echo '<script>alert("'.$msg.'")</script>';
        }

        public static function imagemValida($imagem){
            if($imagem['type'] == 'image/jpeg' || $imagem['type'] == 'image/jpg' || $imagem['type'] == 'image/png'){
                $tamanho = intval($imagem['size']/1024); //conversão para byts
                if($tamanho < 600){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

        public static function updateFile($file, $path){
            $formatoArquivo = explode('.', $file['name']);
            $imagemNome = uniqid().'.'.$formatoArquivo[count($formatoArquivo) - 1];
            if(move_uploaded_file($file['tmp_name'],BASE_DIR_PAINEL.'/'.$path.'/'.$file['name'])){
                return $file['name'];
            }else{
                return false;
            }
        }

        public static function uploadFile($path,$file){
			$formatoArquivo = explode('.',$file['name']);
			$imagemNome = uniqid().'.'.$formatoArquivo[count($formatoArquivo) - 1];
			if(move_uploaded_file($file['tmp_name'],BASE_DIR_PAINEL.'/'.$path.'/'.$imagemNome))
				return $imagemNome;
			else
				return false;
		}

        public static function deleteFile($path, $file){
            @unlink($path.'/'.$file); // @ = ocultar erro do php.
        }

        public static function insert($array){
            $certo = true;
            $nome_tabela = $array['nome_tabela'];
            $query = "INSERT INTO `$nome_tabela` VALUES (null";
            foreach ($array as $key => $value) {
                $nome = $key;
                $valor = $value;
                if($nome == 'acao' || $nome == 'nome_tabela')
                    continue;
                if($value == ''){
                    return false;
                }
                $query.=",?";
                $parametros[] = $value;
            }   
            $query.=")";
            if($certo == true){
                $sql = MySql::conectar()->prepare($query);
                $sql->execute($parametros);
                $lastId = MySql::conectar()->lastInsertId();
                $sql = MySql::conectar()->prepare("UPDATE `$nome_tabela` SET order_id = ? WHERE id = $lastId");
                $sql->execute(array($lastId));
            }
            return $certo;
        }

        public static function update($array, $single = false){
            $certo = true;
            $first = false;
            $nome_tabela = $array['nome_tabela'];
            $query = "UPDATE `$nome_tabela` SET ";
            foreach ($array as $key => $value) {
                $nome = $key;
                if($nome == 'acao' || $nome == 'nome_tabela' || $nome == 'id')
                    continue;
                if($value == ''){
                    return false;
                }
                if($first == false){    // se falso, então é o 1º parâmetro que usamos para montar a query 
                    $first = true;
                    $query.="$nome=?";
                }else{
                    $query.=",$nome=?"; // para ambos os parâmentros é necessário a vírgula na query
                }
                $parametros[] = $value;
            }   
            if($certo == true){
                if($single == false){
                    $parametros[] = $array['id'];
                    $sql = MySql::conectar()->prepare($query.' WHERE id=?');
                    $sql->execute($parametros);
                }else{
                    $sql = MySql::conectar()->prepare($query);
                    $sql->execute($parametros);
                }
            }
            return $certo;
        }

        public static function selectAll($tabela, $order=null, $start=null, $end=null){ //inicializando start e end com nulo
            if($start==null && $end==null && $order==null){
                $sql = MySql::conectar()->prepare("SELECT * FROM `$tabela` ORDER BY order_id ASC");
            }else if($start==null && $end==null && $order!=null){
                $sql = MySql::conectar()->prepare("SELECT * FROM `$tabela` ORDER BY order_id $order");
            }else {
                $sql = MySql::conectar()->prepare("SELECT * FROM `$tabela` ORDER BY order_id $order LIMIT $start, $end");
            }
          
            $sql->execute();
            return $sql->fetchAll();
        }

        public static function deletar($tabela,$id=false){
            if($id == false){
                $sql = MySql::conectar()->prepare("DELETE FROM `$tabela`");
            }else{ 
                $sql = MySql::conectar()->prepare("DELETE FROM `$tabela` WHERE id = $id");
            }
            $sql->execute();
        }

        public static function redirect($url){
            echo '<script>location.href="'.$url.'"</script>';
            die();
        }

        /* 
         *  Método específico para selecionar apenas 1 registro.
         */
        public static function select($tabela, $query = '', $array = ''){
            if($query != false){
                $sql = MySql::conectar()->prepare("SELECT * FROM `$tabela` WHERE $query ");
                $sql->execute($array);
            }else{
                $sql = MySql::conectar()->prepare("SELECT * FROM `$tabela`");
                $sql->execute();
            }
            return $sql->fetch();
        }

        public static function selectQuery($tabela, $query = '', $array = ''){
            if($query != false){
                $sql = MySql::conectar()->prepare("SELECT * FROM `$tabela` WHERE $query ");
                $sql->execute($array);
            }else{
                $sql = MySql::conectar()->prepare("SELECT * FROM `$tabela`");
                $sql->execute();
            }
            return $sql->fetchAll();
        }

        public static function orderItem($table, $oderType, $idItem){ //ordenar registros na tabela (up, down)
            if($oderType == 'up'){
                $infoItemAtual = Painel::select($table, 'id=?', array($idItem));
                $order_id = $infoItemAtual['order_id'];
                $itemBefore = MySql::conectar()->prepare("SELECT * FROM `$table` WHERE order_id < $order_id ORDER BY order_id DESC LIMIT 1");//RETURN: 1 registro antes do item que vou mover. DECRESCENTE
                $itemBefore->execute();
                if($itemBefore == 0){
                    return;
                }
                $itemBefore = $itemBefore->fetch();
                Painel::update(array('nome_tabela'=>$table, 'id'=>$itemBefore['id'], 'order_id'=>$infoItemAtual['order_id']));
                Painel::update(array('nome_tabela'=>$table, 'id'=>$infoItemAtual['id'], 'order_id'=>$itemBefore['order_id']));
            }else if($oderType == 'down'){
                $infoItemAtual = Painel::select($table, 'id=?', array($idItem));
                $order_id = $infoItemAtual['order_id'];
                $itemBefore = MySql::conectar()->prepare("SELECT * FROM `$table` WHERE order_id > $order_id ORDER BY order_id ASC LIMIT 1");//RETURN: 1 registro depois do item que vou mover. ASCENDENTE
                $itemBefore->execute();
                if($itemBefore == 0){
                    return;
                }
                $itemBefore = $itemBefore->fetch();
                Painel::update(array('nome_tabela'=>$table, 'id'=>$itemBefore['id'], 'order_id'=>$infoItemAtual['order_id']));
                Painel::update(array('nome_tabela'=>$table, 'id'=>$infoItemAtual['id'], 'order_id'=>$itemBefore['order_id']));
            }
        }

        public static function generateSlug($str){
            $str = mb_strtolower($str); // string maiúscula com acentuação para minúscula com acentuação
			$str = preg_replace('/(â|á|ã)/', 'a', $str);
			$str = preg_replace('/(ê|é)/', 'e', $str);
			$str = preg_replace('/(í|Í)/', 'i', $str);
			$str = preg_replace('/(ú)/', 'u', $str);
			$str = preg_replace('/(ó|ô|õ|Ô)/', 'o',$str);
			$str = preg_replace('/(_|\/|!|\?|#)/', '',$str);
			$str = preg_replace('/( )/', '-',$str);
			$str = preg_replace('/ç/','c',$str);
			$str = preg_replace('/(-[-]{1,})/','-',$str);
			$str = preg_replace('/(,)/','-',$str);
			$str=strtolower($str); // ignora string maiúscula com acentuação
			return $str;
        }

    }
?>
