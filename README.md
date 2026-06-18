# Iron & Stone - Sistema E-commerce de Materiais de Construção

Este projeto consiste em um sistema completo de e-commerce e portal institucional para a **Iron & Stone**, uma empresa especializada no fornecimento de ferro e pedras para a construção civil. O sistema atende tanto ao cliente final quanto à administração da empresa por meio de um painel administrativo integrado.

---

## 🛠️ Tecnologias Utilizadas

- **Front-end:** HTML5, CSS3 (Vanilla CSS com design responsivo em Montserrat), Vanilla JavaScript para interações assíncronas.
- **Back-end:** PHP (com arquitetura limpa e organizada).
- **Banco de Dados:** MySQL com conexões seguras via **PDO** (Prepared Statements).
- **Segurança:** Criptografia de senhas usando `bcrypt` (função `password_hash` do PHP) e proteção contra ataques **CSRF** em formulários críticos.

---

## 🚀 Funcionalidades Principais

### Área do Cliente (Pública e Logada):
1. **Catálogo de Produtos:** Exibição dinâmica de produtos com paginação, barra de busca por texto e filtros por categoria.
2. **Carrinho de Compras:** Adição de produtos com atualização dinâmica e assíncrona, controle de quantidade e validação em tempo real contra o estoque do banco de dados.
3. **Autenticação Segura:** Criação de conta e Login de usuários.
4. **Histórico de Pedidos:** Acompanhamento do status dos pedidos finalizados.
5. **Fale Conosco:** Formulário de contato que categoriza e grava mensagens direcionadas aos setores responsáveis.

### Painel Administrativo (`/admin`):
1. **Métricas de Vendas:** Dashboard inicial com contagem de pedidos, faturamento e mensagens não lidas.
2. **CRUD de Produtos:** Cadastro completo de novos produtos com definição de categorias, descrição, preços e controle de estoque.
3. **CRUD de Categorias:** Organização dinâmica das categorias ativas do site.
4. **Gestão de Mensagens:** Leitura e controle de status das dúvidas enviadas pelo formulário Fale Conosco.
5. **Gestão de Usuários:** Monitoramento dos usuários cadastrados no sistema.

---

## 📂 Estrutura de Pastas

```text
htdocs/
  ├── admin/            # Painel Administrativo (Dashboard e CRUDs)
  ├── api/              # Endpoints assíncronos (ex: carrinho de compras)
  ├── assets/           # Imagens, logotipos e recursos visuais
  ├── auth/             # Telas de Login, Cadastro e Logout
  ├── includes/         # Arquivos globais (Conexão ao BD, funções e autenticação)
  ├── user/             # Telas da área logada do cliente (carrinho, perfil, pedidos)
  ├── database.sql      # Script SQL de criação do banco de dados
  ├── README.md         # Documento explicativo
  ├── index.html        # Página inicial do site
  ├── produtos.php      # Catálogo público de produtos
  ├── quem-somos.php    # História e informações institucionais
  └── servicos.html     # Serviços prestados pela empresa
```

---

## 🔧 Como Rodar o Projeto Localmente (XAMPP)

1. Baixe o código fonte e coloque a pasta do projeto dentro de `C:\xampp\htdocs\`.
2. Abra o **XAMPP Control Panel** e inicie os módulos **Apache** e **MySQL**.
3. Abra seu navegador e acesse: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
4. Crie um novo banco de dados com o nome `iron_stone`.
5. Selecione o banco `iron_stone`, vá até a aba **SQL**, cole o conteúdo do arquivo `database.sql` e execute.
6. Acesse o site no navegador pelo endereço:
   ```text
   http://localhost/index.html
   ```

---

## 🔑 Credenciais Padrão (Administrador)

Para testar o painel administrativo, acesse a página de Login e utilize os dados padrão:
- **E-mail:** `admin@ironstone.com`
- **Senha:** `admin123`
