<?php
    include('../../includeConstants.php');
    $data['sucesso'] = true;
    $data['msg']= "";

    /* code here */
    if(Painel::logado() == false){
        die("You're not allowed to do that.");
    }

    if(isset($_POST['tipo_acao']) && $_POST['tipo_acao'] == 'cadastrar_cliente'){
        sleep(1.5);
        $nome = $_POST['nome'];
        $email = $_POST['email'];

        if(isset($_POST['email'])){
            $sql = MySql::conectar()->prepare("SELECT `id` FROM `tb_site.clientes` WHERE email = ?");
            $sql->execute(array($email));
                if($sql->rowCount() != 0){
                    $data['sucesso'] = false;
                    $data['msg'] = " E-mail já cadastrado.";
                }
        }

        $senha = $_POST['password'];
        $img = "";

        if(isset($_FILES['img'])){
            if(Painel::imagemValida($_FILES['img'])){
                $img = $_FILES['img'];
            }else{
                $img = "";
                $data['sucesso'] = false;
                $data['msg'] = " Arquivo ou tamanho da imagem inválido.";
            }
        }

        if($data['sucesso'] == true){
            if(is_array($img))
                $img = Painel::uploadFile($img);
            $sql = MySql::conectar()->prepare("INSERT INTO `tb_site.clientes` VALUES (null,?,?,?,?)");
            $sql->execute(array($nome, $email, $senha, $img));
            $data['msg'] = " Cliente cadastrado com sucesso!";
        }
    }else if(isset($_POST['tipo_acao']) && $_POST['tipo_acao'] == 'editar_cliente'){
        sleep(1.5);
        $id = $_POST['id'];

        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = $_POST['password'];
        $img = $_POST['imagem_original'];

        if($nome == '' || $email == '' || $senha == ''){
            $data['sucesso'] = false;
            $data['msg'] = " Campos vázios não são permitidos!";
        }

        if(isset($_FILES['img'])){
            if(Painel::imagemValida($_FILES['img'])){
                @unlink('../uploads/'.$img);
                $img = $_FILES['img'];
            }else{
                $data['sucesso'] = false;
                $data['msg'] = " Tipo do arquivo ou tamanho da imagem inválido.";
            }
        }

        if($data['sucesso']){
            if(is_array($img)){
                $img = Painel::uploadFile($img);
            }
            $sql = MySql::conectar()->prepare("UPDATE `tb_site.clientes` SET nome = ?, email = ?, senha = ?, img = ? WHERE id = $id");
            $sql->execute(array($nome, $email, $senha, $img));
            
            $data['msg'] = " O cliente foi atualizado com sucesso!";
        }

    }else if(isset($_POST['tipo_acao']) && $_POST['tipo_acao'] == 'excluir_cliente'){
        $id = $_POST['id'];
        $sql = MySql::conectar()->prepare("SELECT img FROM `tb_site.clientes` WHERE id = $id");

        $img = $sql->fetch()['img'];
        @unlink('../uploads/'.$img);

        MySql::conectar()->exec("DELETE FROM `tb_site.clientes` WHERE id = $id");
    }
    die(json_encode($data));
?>