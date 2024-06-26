<?php 
    verificaPermissao(1); 
    $paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $porPagina = 10; //resultados por pagina
    $automoveis = Painel::selectAll('tb_site.automoveis', null, ($paginaAtual - 1) * $porPagina, $porPagina);
    $concess = Painel::selectAll('tb_site.concessionarias');
?>
<div class="box-content">
    <h2><i class="fa-solid fa-car-rear"></i><i style="font-size: 15px;" class="fa-solid fa-gears"></i> Gerenciar Autmóveis | Cadastro & Listagem</h2>

    <form method="post" enctype="multipart/form-data">
    <?php 
        if(isset($_POST['acao'])){
            $marca = $_POST['marca'];
            $modelo = $_POST['modelo'];
            $versao = $_POST['versao'];
            $cor = $_POST['cor'];
            $preco = Painel::formatarMoedaBD($_POST['preco']);
            $quilometragem = $_POST['quilometragem'];
            $cambio = $_POST['cambio'];
            $combustivel = $_POST['combustivel'];
            $anoFab = $_POST['ano_fab'];
            $anoMod = $_POST['ano_mod'];
            $idConcessionaria = $_POST['id_concessionaria'];

            $imagens = array();
            $amountFiles = count($_FILES['imagem']['name']);

            $sucesso = true;

            if($marca == '' || $modelo == '' || $versao == '' || $cor == '' || $preco == '' || $quilometragem == '' || $cambio == '' || $combustivel == '' || $anoFab == '' || $anoMod == '' || $idConcessionaria == ''){
                $sucesso = false;
                Painel::alert('erro', 'Campos vázios não são permitidos.');
            }

            if($quilometragem < 0 || $quilometragem > 500000){
                $sucesso = false;
                Painel::alert('erro', 'Quilometragem inválida.');
            }

            if(strlen($anoFab) != 4 && ($anoFab < 2010 || $anoFab > date("Y"))){
               $sucesso = false;
               Painel::alert('erro', 'Ano de fabricação inválido.');
            }

            if(strlen($anoMod) != 4 && ($anoMod < 2010 || $anoMod > date("Y"))){
                $sucesso = false;
                Painel::alert('erro', 'Ano modelo inválido.');
            }

            if($_FILES['imagem']['name'][0] != ''){
                for($i=0; $i < $amountFiles; $i++){
                    $currentImg = ['type'=>$_FILES['imagem']['type'][$i], 
                    'size'=>$_FILES['imagem']['size'][$i]];
                    if(!Painel::imagemValida($currentImg)){
                        $sucesso = false;
                        Painel::alert('erro', 'Uma das imagens selecionadas são inválidas.');
                        break;
                    }
                }
            }else{
                $sucesso = false;
                Painel::alert('erro', 'É necessário selecionar pelo menos uma imagem!');
            }

            if($sucesso){
                for($i=0; $i < $amountFiles; $i++){
                    $currentImg = ['tmp_name'=>$_FILES['imagem']['tmp_name'][$i], 
                    'name'=>$_FILES['imagem']['name'][$i]];
                    $imagens[] = Painel::uploadFile('uploads/automoveis',$currentImg);
                }

                $sql = MySql::conectar()->prepare("INSERT INTO `tb_site.automoveis` VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $slug = Painel::generateSlug($modelo.' '.$versao);
                $sql->execute(array($idConcessionaria, $marca, $modelo, $versao, $anoFab, $anoMod, $preco, $quilometragem, $cambio, $combustivel, $cor, $slug, 0 ,0));
                $lastId = MySql::conectar()->lastInsertId();
                MySql::conectar()->exec("UPDATE `tb_site.automoveis` SET order_id = $lastId WHERE id = $lastId");

				foreach ($imagens as $key => $value) {
					MySql::conectar()->exec("INSERT INTO `tb_site.imagens_automoveis` VALUES (null,$lastId,'$value',0)");
                    $lastId2 = MySql::conectar()->lastInsertId();
                    MySql::conectar()->exec("UPDATE `tb_site.imagens_automoveis` SET order_id = $lastId2 WHERE id = $lastId2");
				}
                
                $automoveis = Painel::selectAll('tb_site.automoveis');
            }
        }
    ?>
        <div class="card-title"><i class="fa-solid fa-car-rear"></i><i class="fa-solid fa-plus"></i> Cadastrar Automóvel</div>

		<div class="form-group W50 right">
            <label>Marca</label>
            <input type="text" name="marca" required />
        </div><!-- form-group -->

        <div class="form-group W50 left">
            <label>Modelo</label>
            <input type="text" name="modelo" required />
        </div><!-- form-group -->

		<div class="form-group W50 left">
            <label>Versao</label>
            <input type="text" name="versao" required />
        </div><!-- form-group -->

		<div class="form-group W50 right">
			<label>Cor do automóvel</label>
			<input type="text" name="cor" required />
		</div><!--form-group-->

        <div class="form-group W50 left">
            <label>Preço</label>
            <input type="text" name="preco" required />
        </div><!-- form-group -->

        <div class="form-group W50 right">
            <label>Quilometragem</label>
            <input type="number" name="quilometragem" min="0" max="500000" required 
            placeholder="max: 500.000 km" />
        </div><!-- form-group -->
        <div class="clear"></div>

        <div class="form-group right" style="width: 25%;">
            <label>Ano Modelo</label>
            <input min="2010" max="<?php echo date("Y");?>" type="number" name="ano_mod" required
            placeholder="min/max: 2010/<?php echo date("Y");?>" />
        </div><!-- form-group -->

        <div class="form-group right" style="width: 24%; padding-right: 15px;">
            <label>Ano de Fabricação</label>
            <input min="2010" max="<?php echo date("Y");?>" type="number" name="ano_fab" required
            placeholder="min/max: 2010/<?php echo date("Y");?>" />
        </div><!-- form-group -->
        
        <div class="form-group left W50">
            <label>Concessionária</label>
            <select name="id_concessionaria" required>
                <?php 
                    $concess = Painel::selectAll('tb_site.concessionarias'); 
                    foreach ($concess as $key => $value) {
                ?>
                <option <?php if($value['id'] == @$_POST['id_concessionaria']) echo 'selected'; ?> value="<?php echo $value['id']; ?>"><?php echo $value['nome']; ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group left W50" style="margin-right: 20px;">
            <label>Câmbio</label>
            <select name="cambio" required >
                <?php 
                    $cambios = \model\Automovel::$cambios;
                    foreach ($cambios as $key => $camb) {
                ?>
                <option value="<?php echo $key; ?>"><?php echo $camb; ?></option>
                <?php } ?>
            </select>
        </div><!-- form-group -->

        <div class="form-group right W50">
            <label>Combustível</label>
            <select name="combustivel" required>
                <?php 
                    $combustiveis = \model\Automovel::$combustiveis;
                    foreach ($combustiveis as $key => $comb) {
                ?>
                <option value="<?php echo $key; ?>"><?php echo $comb; ?></option>
                <?php } ?>
            </select>
        </div><!-- form-group -->

        <div class="form-group">
            <label>Selecione Imagens</label>
            <input type="file" multiple name="imagem[]" required />
        </div><!-- form-group -->

        <div class="form-group">
            <input type="submit" name="acao" value="Cadastrar!" />
        </div><!-- form-group -->
    </form>

    <div style="margin-top: 30px;" class="wrapper-table">
        <div class="card-title"><i class="fa-solid fa-car-rear"></i><i class="fa-solid fa-list-check"></i> Automóveis Cadastrados</div>
            <table id="listar-automoveis" style="margin: 5px 0;">
                <tr>
                    <td>Concessionária</td>
                    <td>Marca</td>
                    <td>Modelo</td>
                    <td>Preço</td>
                    <td>Ano/Modelo</td>
                    <td>Quilometragem</td>
                    <td>Visualização</td>
                </tr>
                
        <?php 
            foreach ($automoveis as $key => $auto) {
                $auto['preco'] = Painel::convertMoney($auto['preco']);
                $auto['quilometragem'] = Painel::convertKm($auto['quilometragem']);

                foreach ($concess as $key => $conc) {
                    if($auto['id_concessionaria'] == $conc['id']){
        ?>
            <tr class="body">
                <td><?php echo $conc['nome'];?></td>
                <td><?php echo $auto['marca'];?></td>
                <td><?php echo $auto['modelo'];?></td>
                <td>R$ <?php echo $auto['preco'];?></td>
                <td><?php echo $auto['ano_fab'];?>/<?php echo $auto['ano_mod'];?></td>
                <td><?php echo $auto['quilometragem'];?> Km</td>
                <td><a class="btn-view" href="<?php echo INCLUDE_PATH_PAINEL ?>editar-automovel?id=<?php echo $auto['id']; ?>"><i class="fa-solid fa-eye"></i> Visualizar</a></td>
            </tr>
    <?php       
                    }
                }
            }             
    ?>
        </table>
    </div><!-- wrapper-table -->
    <div class="paginacao">
        <?php 
            $totalPaginas = ceil(count(Painel::selectAll('tb_site.automoveis')) / $porPagina);
            for($i=1; $i<=$totalPaginas; $i++){
                if($i == $paginaAtual){
                    echo '<a class="page-selected" href="'.INCLUDE_PATH_PAINEL.'gerenciar-automoveis?pagina='.$i.'">'.$i.'</a>';
                }else
                    echo '<a href="'.INCLUDE_PATH_PAINEL.'gerenciar-automoveis?pagina='.$i.'">'.$i.'</a>';
            }
        ?>
    </div><!-- paginacao -->
</div><!-- box-content -->