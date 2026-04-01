# Atividade-Avaliativa-

Funcionalidades

Cadastro de usuário com:

Nome
Email
Senha (criptografada)
Mensagem

Validações:

Nome obrigatório
Email válido
Senha forte (mín. 8 caracteres, maiúscula, minúscula, número e símbolo)
Mensagem com limite de 250 caracteres

Segurança:

Sanitização de dados (proteção contra XSS)
Hash de senha com password_hash()
Uso de PDO com prepared statements (proteção contra SQL Injection)

Interface:

Layout moderno com CSS
Contador de caracteres em tempo real
Lista de mensagens exibida ao lado
  Tecnologias Utilizadas
PHP (PDO)
MySQL
HTML5
CSS3
JavaScript (Vanilla)
  Configuração do Projeto
1. Clonar ou copiar o projeto

Coloque o arquivo no seu servidor local (ex: XAMPP):

htdocs/seu-projeto/
2. Criar o banco de dados

No MySQL, execute:

CREATE DATABASE formulario;

USE formulario;

CREATE TABLE contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    mensagem TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
3. Configurar conexão

No arquivo PHP, ajuste se necessário:

$host = "localhost";
$db   = "formulario";
$user = "root";
$pass = "SUA_SENHA";
4. Executar o projeto

Abra no navegador:

http://localhost/seu-projeto
  Regras de Validação da Senha

A senha deve conter:

Pelo menos 8 caracteres
1 letra maiúscula
1 letra minúscula
1 número
1 caractere especial (!@#$%^&*)
  Estrutura do Código
Conexão PDO → conexão segura com o banco
Função sanitizar() → evita XSS
Validação PHP → garante integridade dos dados
Inserção no banco → via prepared statements
Listagem de dados → exibição dinâmica dos contatos
  Interface
Design moderno com gradiente
Formulário responsivo
Lista lateral de contatos
Feedback visual de erro e sucesso
  Melhorias Futuras
Sistema de login/autenticação
Exclusão e edição de contatos
Paginação da lista
Upload de arquivos
API REST
Responsividade mobile mais avançada
  Autor

Projeto desenvolvido para fins de estudo em:

PHP
Banco de dados
Segurança web

