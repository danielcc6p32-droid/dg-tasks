<div align="center">

# ✅ DG Tasks

### Organização, acompanhamento e gestão de tarefas

**Um projeto DG Tech — mais clareza para acompanhar atividades e prioridades.**

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-PDO-003B57?logo=sqlite&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-Interface-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-Responsivo-1572B6?logo=css3&logoColor=white)
![Status](https://img.shields.io/badge/Status-Demonstração%20funcional-2ea44f)

[**Código-fonte**](https://github.com/danielcc6p32-droid/dg-tasks) · **Demo online: em preparação**

</div>

---

## Sobre o projeto

O **DG Tasks** é um sistema web para **organização e acompanhamento de tarefas**, construído com PHP puro, HTML, CSS e SQLite. Permite registrar atividades, atribuir responsáveis, classificar prioridades e acompanhar o andamento das demandas.

O objetivo é demonstrar competências de desenvolvimento web com um cenário próximo à rotina de equipes de TI, suporte, infraestrutura e operações. O sistema integra o portfólio DG Tech e os estudos em **Desenvolvimento de Sistemas**.

## Funcionalidades

- **CRUD de tarefas:** criar, visualizar, editar e excluir atividades.
- **Cadastro de informações:** título, responsável, descrição, prioridade e status.
- **Prioridades:** Baixa, Média e Alta.
- **Acompanhamento de status:** Pendente, Em andamento e Concluída.
- **Pesquisa textual** por título, descrição ou responsável e **filtro por status**.
- **Cadastro e autenticação de usuários**, com senhas protegidas por hash.
- **Acesso demonstrativo sem cadastro**, com conta de teste isolada para cada visitante.
- **Isolamento dos dados por usuário** nas operações de consulta e manutenção.
- **Interface responsiva**, com layout escuro e visual organizado.

> Exemplo de uso: registrar a tarefa “Atualizar servidores”, atribuir ao setor de TI, definir prioridade Alta e acompanhar sua evolução de Pendente até Concluída.

## Tecnologias e conceitos aplicados

| Camada | Tecnologia / conceito |
| --- | --- |
| Backend | PHP 8.1+ sem framework |
| Banco de dados | SQLite com PDO |
| Frontend | HTML5 e CSS3 responsivo |
| Autenticação | Sessões PHP e hash de senhas |
| Segurança | Token CSRF, escape de saída, consultas preparadas e cabeçalhos de segurança |
| Versionamento | Git e GitHub |

## Estrutura do projeto

```text
dg-tasks/
├── public/
│   ├── index.php       # Interface, autenticação e operações do sistema
│   └── style.css       # Estilos e responsividade
├── private/
│   ├── README.txt      # Orientações sobre arquivos privados
│   └── dados.sqlite    # Criado automaticamente; NÃO versionado
├── .gitignore
└── README.md
```

O diretório `public/` deve ser a raiz do servidor web. O banco de dados fica na pasta `private/`, que não deve ser acessível pela internet.

## Executar localmente

**Pré-requisitos:** PHP 8.1 ou superior, com `PDO`, `pdo_sqlite` e `mbstring` habilitados.

1. Clone o repositório e abra a pasta:

   ```bash
   git clone https://github.com/danielcc6p32-droid/dg-tasks.git
   cd dg-tasks
   ```

2. Inicie o servidor embutido do PHP:

   ```bash
   php -S localhost:8001 -t public
   ```

3. Abra **http://localhost:8001** no navegador.
4. Clique em **Entrar na demonstração (sem cadastro)** e crie uma tarefa para avaliar o fluxo.

O banco SQLite e suas tabelas são criados automaticamente na primeira execução, desde que a pasta `private/` permita escrita pelo processo PHP.

## Cuidados para implantação

- Aponte o **document root** para `public/` e mantenha `private/` fora da área acessível por HTTP.
- Utilize armazenamento **persistente** para conservar tarefas e usuários após reinicializações.
- Não envie o arquivo SQLite para repositórios públicos.
- Antes da exposição pública, valide permissões de arquivos, sessões e limites de requisições.

## Próximas evoluções

- Prazos e datas de vencimento.
- Visão Kanban e indicadores de produtividade.
- Histórico de alterações.
- Notificações de tarefas em atraso.
- Perfis de acesso para equipes e supervisores.

---

**Desenvolvido por Daniel Pereira Gonçalves**  
*DG Tech · Portfólio de Desenvolvimento de Sistemas*
