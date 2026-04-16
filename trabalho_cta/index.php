<?php
session_start();

// -------------------- CONEXÃO PDO --------------------
$host = "localhost";
$db   = "formulario";
$user = "root";
$pass = "&tec77@info!"; 
$charset = "utf8mb4";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

function sanitizar($dado) {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}

// -------------------- DELETAR --------------------
if (isset($_POST['deletar'])) {
    $id = (int) $_POST['deletar'];

    $stmt = $pdo->prepare("DELETE FROM contatos WHERE id = :id");
    $stmt->execute([":id" => $id]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -------------------- EDITAR --------------------
$editando = false;
$edit_id = null;
$nome = $email = $mensagem = '';

if (isset($_GET['editar'])) {
    $edit_id = (int) $_GET['editar'];

    $stmt = $pdo->prepare("SELECT * FROM contatos WHERE id = :id");
    $stmt->execute([":id" => $edit_id]);
    $dados = $stmt->fetch();

    if ($dados) {
        $nome = $dados['nome'];
        $email = $dados['email'];
        $mensagem = $dados['mensagem'];
        $editando = true;
    }
}

// -------------------- FORM --------------------
$erros = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['deletar'])) {

    $nome = sanitizar($_POST["nome"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $senha = $_POST["senha"] ?? "";
    $mensagem = sanitizar($_POST["mensagem"] ?? "");

    if (strlen($nome) < 1) $erros[] = "Nome inválido";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = "Email inválido";
    if (strlen($mensagem) > 250) $erros[] = "Mensagem muito longa";

    if (!$editando) {
        if (
            strlen($senha) < 8 ||
            !preg_match('/[A-Z]/', $senha) ||
            !preg_match('/[a-z]/', $senha) ||
            !preg_match('/[0-9]/', $senha) ||
            !preg_match('/[!@#$%^&*]/', $senha)
        ) $erros[] = "Senha fraca";
    }

    if (empty($erros)) {

        if (isset($_POST['edit_id'])) {

            $id = (int) $_POST['edit_id'];

            // bloqueia duplicada
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM contatos 
                WHERE mensagem = :mensagem AND id != :id
            ");
            $stmt->execute([
                ":mensagem" => $mensagem,
                ":id" => $id
            ]);

            if ($stmt->fetchColumn() == 0) {

                $stmt = $pdo->prepare("
                    UPDATE contatos 
                    SET mensagem = :mensagem 
                    WHERE id = :id
                ");

                $stmt->execute([
                    ":mensagem" => $mensagem,
                    ":id" => $id
                ]);
            }

        } else {

            $stmt = $pdo->prepare("SELECT senha FROM contatos WHERE email = :email LIMIT 1");
            $stmt->execute([":email" => $email]);
            $usuario = $stmt->fetch();

            if (!$usuario || password_verify($senha, $usuario['senha'])) {

                $senhaHash = $usuario
                    ? $usuario['senha']
                    : password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO contatos (nome, email, senha, mensagem)
                    VALUES (:nome, :email, :senha, :mensagem)
                ");

                $stmt->execute([
                    ":nome" => $nome,
                    ":email" => $email,
                    ":senha" => $senhaHash,
                    ":mensagem" => $mensagem
                ]);
            }
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// -------------------- LISTA --------------------
$stmt = $pdo->query("SELECT id, nome, email, mensagem FROM contatos ORDER BY criado_em DESC");
$contatos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Formulário</title>

<!-- 🔥 CSS EXTERNO -->
<link rel="stylesheet" href="style.css">

</head>
<body>

<div class="container">

<div class="form-area">
<h2><?= $editando ? "Editar Mensagem" : "Formulário" ?></h2>

<?php foreach ($erros as $e): ?>
<p class="erro"><?= $e ?></p>
<?php endforeach; ?>

<form method="POST">

<?php if ($editando): ?>
<input type="hidden" name="edit_id" value="<?= $edit_id ?>">
<?php endif; ?>

<label>Nome</label>
<input type="text" name="nome" value="<?= $nome ?>" <?= $editando ? 'readonly' : '' ?>>

<label>Email</label>
<input type="email" name="email" value="<?= $email ?>" <?= $editando ? 'readonly' : '' ?>>

<?php if (!$editando): ?>
<label>Senha</label>
<input type="password" name="senha">
<?php endif; ?>

<label>Mensagem</label>
<textarea name="mensagem" maxlength="250"><?= $mensagem ?></textarea>

<button type="submit">
<?= $editando ? "Atualizar" : "Enviar" ?>
</button>

</form>
</div>

<div class="lista-contatos">
<h2>Mensagens</h2>

<?php foreach ($contatos as $c): ?>
<div class="contato-card">

<strong><?= htmlspecialchars($c['nome']) ?></strong>
<small><?= htmlspecialchars($c['email']) ?></small>
<p><?= nl2br(htmlspecialchars($c['mensagem'])) ?></p>

<div class="acoes">

<form method="GET" style="display:inline;">
<input type="hidden" name="editar" value="<?= $c['id'] ?>">
<button class="btn-editar">Editar</button>
</form>

<form method="POST" style="display:inline;" onsubmit="return confirm('Excluir?')">
<input type="hidden" name="deletar" value="<?= $c['id'] ?>">
<button class="btn-deletar">Deletar</button>
</form>

</div>

</div>
<?php endforeach; ?>

</div>

</div>

</body>
</html>
