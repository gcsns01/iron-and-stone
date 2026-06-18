# Trabalho de Desenvolvimento Web - Iron & Stone

Este é o projeto desenvolvido para o trabalho final da disciplina, representando o sistema de e-commerce e site da empresa **Iron & Stone** (empresa de venda de ferro e pedras para construção).

O site possui a parte institucional para os clientes verem os produtos e serviços, uma área de login para fazer compras, e uma área de administração para gerenciar o sistema.

## Tecnologias

- **PHP** no back-end
- **MySQL** para o banco de dados (usando conexão PDO e prepared statements para segurança)
- **HTML e CSS** para o visual (Montserrat, layout adaptado para telas de computadores)
- **JavaScript** em algumas funções (carrinho de compras e mensagens de confirmação)

## O que o sistema faz

### Cliente
- Catálogo com todos os produtos cadastrados
- Busca de produtos por nome e filtro por categorias
- Carrinho de compras integrado com o estoque
- Histórico com os pedidos realizados após o login
- Formulário de contato (Fale Conosco) que salva as mensagens no banco

### Administrador (Painel Admin)
- Painel com resumo das vendas, faturamento e mensagens
- Cadastro, edição e exclusão de produtos (CRUD de produtos)
- Cadastro e exclusão de categorias (CRUD de categorias)
- Visualização e controle das mensagens que os clientes enviaram
- Visualização dos usuários cadastrados no site

## Pastas do Projeto

- `admin/`: Arquivos da área do administrador
- `api/`: Código do carrinho de compras
- `assets/`: Imagens e logos
- `auth/`: Login, cadastro e logout
- `includes/`: Conexão com o banco (`db.php`) e funções gerais do sistema
- `user/`: Carrinho e histórico de pedidos do cliente
- `database.sql`: Arquivo com a estrutura do banco de dados

## Instalação e Configuração (XAMPP local)

1. Baixe a pasta do projeto e coloque dentro de `C:\xampp\htdocs\`.
2. Abra o painel do XAMPP e inicie o Apache e o MySQL.
3. Acesse o phpMyAdmin em `http://localhost/phpmyadmin/`.
4. Crie um banco de dados chamado `iron_stone`.
5. Importe o arquivo `database.sql` para dentro deste banco.
6. Acesse o site digitando no navegador: `http://localhost/index.html`

## Usuário Admin para testes

Para entrar como administrador no site:
- **E-mail:** `admin@ironstone.com`
- **Senha:** `admin123`
