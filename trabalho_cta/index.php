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

// -------------------- FUNÇÃO SANITIZAR --------------------
function sanitizar($dado) {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}

// -------------------- MENSAGEM DE SUCESSO --------------------
$mensagem_sucesso = '';


// -------------------- PROCESSA FORMULÁRIO --------------------
$erros = [];
$nome = $email = $mensagem = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = sanitizar($_POST["nome"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $senha = $_POST["senha"] ?? "";
    $mensagem = sanitizar($_POST["mensagem"] ?? "");

    // validações
    if (strlen($nome) < 1) $erros[] = "Nome inválido";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = "Email inválido";
    if (
        strlen($senha) < 8 ||
        !preg_match('/[A-Z]/', $senha) ||
        !preg_match('/[a-z]/', $senha) ||
        !preg_match('/[0-9]/', $senha) ||
        !preg_match('/[!@#$%^&*]/', $senha)
    ) $erros[] = "Senha fraca";

    if (strlen($mensagem) > 250) $erros[] = "Mensagem muito longa";

    if (empty($erros)) {

        // Verifica se email já existe
        $stmt = $pdo->prepare("SELECT senha FROM contatos WHERE email = :email LIMIT 1");
        $stmt->execute([":email" => $email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            if (!password_verify($senha, $usuario['senha'])) {
                $erros[] = "Senha incorreta para este email!";
            }
        }

        if (empty($erros)) {

            $senhaHash = $usuario
                ? $usuario['senha']
                : password_hash($senha, PASSWORD_DEFAULT);

            // Evita mensagem duplicada (extra proteção)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM contatos 
                WHERE email = :email AND mensagem = :mensagem
            ");
            $stmt->execute([
                ":email" => $email,
                ":mensagem" => $mensagem
            ]);

            if ($stmt->fetchColumn() > 0) {
                $erros[] = "Mensagem já enviada!";
            } else {

                $sql = "INSERT INTO contatos (nome, email, senha, mensagem) 
                        VALUES (:nome, :email, :senha, :mensagem)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ":nome" => $nome,
                    ":email" => $email,
                    ":senha" => $senhaHash,
                    ":mensagem" => $mensagem
                ]);

                // 🔥 REDIRECIONAMENTO (PRG)
                header("Location: " . $_SERVER['PHP_SELF'] . "?sucesso=1");
                exit;
            }
        }
    }
}

// -------------------- BUSCA CONTATOS --------------------
$stmt = $pdo->query("SELECT nome, email, mensagem FROM contatos ORDER BY criado_em DESC");
$contatos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Formulário + Lista</title>
<style>
/* ==================== CSS ==================== */
body {
  font-family: 'Segoe UI', Tahoma, sans-serif;
  margin: 0;
  background: linear-gradient(135deg, #4facfe, #00f2fe);
  min-height: 100vh;
}

.container {
  display: flex;
  gap: 20px;
  padding: 20px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.form-area {
  flex: 2;
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

label {
  font-weight: 600;
  color: #555;
}

input, textarea {
  width: 100%;
  padding: 10px 12px;
  margin: 6px 0 12px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 14px;
}

input:focus, textarea:focus {
  border-color: #4facfe;
  outline: none;
  box-shadow: 0 0 8px rgba(79,172,254,0.3);
}

textarea {
  resize: none;
}

button {
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #4facfe, #00c6ff);
  color: white;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}

button:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.erro {
  color: #e74c3c;
  font-size: 13px;
  margin-top: -8px;
  margin-bottom: 8px;
}

#contador {
  text-align: right;
  font-size: 12px;
  color: #777;
  margin-bottom: 10px;
}

.lista-contatos {
  flex: 1;
  max-width: 350px;
  background: #ffffffdd;
  padding: 20px;
  border-radius: 12px;
  overflow-y: auto;
  max-height: 80vh;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.lista-contatos h2 {
  text-align: center;
  margin-bottom: 15px;
}

.contato-card {
  border-bottom: 1px solid #ccc;
  padding: 10px 0;
}

.contato-card strong {
  font-size: 16px;
  color: #333;
}

.contato-card small {
  display: block;
  color: #777;
  font-size: 12px;
}

.contato-card p {
  margin: 5px 0 0;
  font-size: 14px;
  color: #555;
}
.sucesso { color: green; font-weight: bold; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="container">

  <!-- FORMULÁRIO -->
  <div class="form-area">
    <h2>Formulário de Contato</h2>

    <?php if ($mensagem_sucesso): ?>
      <div class="sucesso"><?= $mensagem_sucesso ?></div>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
      <div class="erros">
        <?php foreach ($erros as $e): ?>
          <p><?= $e ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form id="formulario" method="POST">
      <label>Nome:</label>
      <input type="text" name="nome" id="nome" required value="<?= $nome ?>">

      <label>Email:</label>
      <input type="email" name="email" id="email" required value="<?= $email ?>">

      <label>Senha:</label>
      <input type="password" name="senha" id="senha" required>

      <label>Mensagem:</label>
      <textarea name="mensagem" id="mensagem" maxlength="250" required><?= $mensagem ?></textarea>
      <div id="contador"><?= strlen($mensagem) ?> / 250</div>

      <button type="submit">Enviar</button>
    </form>
  </div>

  <!-- LISTA LATERAL -->
  <div class="lista-contatos">
    <h2>Mensagens Recebidas</h2>
    <?php if ($contatos): ?>
      <?php foreach ($contatos as $c): ?>
        <div class="contato-card">
          <strong><?= htmlspecialchars($c['nome']) ?></strong><br>
          <small><?= htmlspecialchars($c['email']) ?></small>
          <p><?= nl2br(htmlspecialchars($c['mensagem'])) ?></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Nenhum contato ainda.</p>
    <?php endif; ?>
  </div>

</div>

<script>
// ==================== JS ====================
const mensagemInput = document.getElementById("mensagem");
const contador = document.getElementById("contador");

mensagemInput.addEventListener("input", () => {
  contador.textContent = `${mensagemInput.value.length} / 250`;
});
</script>
</body>
</html>
