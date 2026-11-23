<?php
declare(strict_types=1);
// Projeto didático DG Tech. PHP puro + SQLite; sem dependências externas.
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Lax']);
    session_start();
}
const APP_TITLE = 'DG Tasks';
const APP_TYPE = 'tasks';
const STATUSES = ['Pendente','Em andamento','Concluída'];
const CATEGORIES = ['Baixa','Média','Alta'];
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void { if (!hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Token inválido. Atualize a página.'); } }
function redirect(string $to='index.php'): never { header('Location: '.$to, true, 303); exit; }
function db(): PDO {
    static $pdo=null;
    if ($pdo instanceof PDO) return $pdo;
    $dir=dirname(__DIR__).'/private';
    if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('Não foi possível criar pasta privada.');
    $pdo=new PDO('sqlite:'.$dir.'/dados.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA busy_timeout=5000');
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, password TEXT NOT NULL)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS records (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, name TEXT NOT NULL, detail TEXT NOT NULL DEFAULT '', category TEXT NOT NULL, status TEXT NOT NULL, owner TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    // Migração de instalações locais anteriores, sem expor dados de outros usuários.
    $cols=$pdo->query('PRAGMA table_info(records)')->fetchAll();
    if (!in_array('user_id',array_column($cols,'name'),true)) {
        $pdo->exec('ALTER TABLE records ADD COLUMN user_id INTEGER NOT NULL DEFAULT 0');
    }
    return $pdo;
}
function auth(): bool { return !empty($_SESSION['user_id']); }
function uid(): int { return (int)($_SESSION['user_id'] ?? 0); }
function field(string $key, int $max=180): string { return mb_substr(trim((string)($_POST[$key] ?? '')),0,$max); }
$pdo=db(); $error=''; $notice='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action=(string)($_POST['action'] ?? '');
    if ($action==='register') {
        $username=field('username',40);
        $password=(string)($_POST['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,40}$/',$username)) $error='Usuário: 3 a 40 caracteres, letras, números, ponto, hífen ou underline.';
        elseif (strlen($password)<12 || strlen($password)>128) $error='A senha deve ter entre 12 e 128 caracteres.';
        else {
            try {
                $q=$pdo->prepare('INSERT INTO users(username,password) VALUES (?,?)');
                $q->execute([$username,password_hash($password,PASSWORD_DEFAULT)]);
                session_regenerate_id(true); $_SESSION['user_id']=(int)$pdo->lastInsertId(); redirect();
            } catch (PDOException $ex) { $error='Este nome de usuário já está em uso.'; }
        }
    } elseif ($action==='demo') {
        // Cada visitante ganha uma conta isolada, sem senha pública compartilhada.
        $demo='demo_'.bin2hex(random_bytes(8));
        $q=$pdo->prepare('INSERT INTO users(username,password) VALUES (?,?)');
        $q->execute([$demo,password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT)]);
        session_regenerate_id(true); $_SESSION['user_id']=(int)$pdo->lastInsertId(); $_SESSION['demo']=true; redirect();
    } elseif ($action==='login') {
        if ((int)($_SESSION['fails'] ?? 0)>=8) { $error='Muitas tentativas. Feche o navegador e tente mais tarde.'; }
        else {
            $q=$pdo->prepare('SELECT * FROM users WHERE username=?'); $q->execute([field('username',60)]); $u=$q->fetch();
            if ($u && password_verify((string)($_POST['password'] ?? ''),$u['password'])) {
                session_regenerate_id(true); $_SESSION['user_id']=(int)$u['id']; $_SESSION['fails']=0; $_SESSION['demo']=false; redirect();
            }
            $_SESSION['fails']=(int)($_SESSION['fails'] ?? 0)+1; $error='Usuário ou senha inválidos.';
        }
    } elseif ($action==='logout') { $_SESSION=[]; session_destroy(); redirect(); }
    elseif (!auth()) { http_response_code(403); exit('Acesso negado.'); }
    elseif ($action==='password') {
        if (!empty($_SESSION['demo'])) { http_response_code(403); exit('Conta demonstrativa não permite alterar senha.'); }
        $q=$pdo->prepare('SELECT password FROM users WHERE id=?'); $q->execute([$_SESSION['user_id']]); $hash=$q->fetchColumn();
        $new=(string)($_POST['new_password'] ?? '');
        if (!password_verify((string)($_POST['old_password'] ?? ''),(string)$hash)) $error='Senha atual incorreta.';
        elseif (strlen($new)<12 || $new==='admin') $error='Use pelo menos 12 caracteres e uma senha diferente de admin.';
        else { $q=$pdo->prepare('UPDATE users SET password=? WHERE id=?');$q->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['user_id']]);$notice='Senha atualizada.'; }
    } elseif ($action==='save') {
        $id=filter_var($_POST['id'] ?? 0,FILTER_VALIDATE_INT) ?: 0;
        $name=field('name',120); $detail=field('detail',500); $category=field('category',50);$status=field('status',50);$owner=field('owner',120);
        if ($name==='' || !in_array($category,CATEGORIES,true) || !in_array($status,STATUSES,true)) $error='Preencha o nome, categoria e status válidos.';
        else {
            if ($id>0) { $q=$pdo->prepare('UPDATE records SET name=?,detail=?,category=?,status=?,owner=? WHERE id=? AND user_id=?');$q->execute([$name,$detail,$category,$status,$owner,$id,uid()]); }
            else { $q=$pdo->prepare('INSERT INTO records(name,detail,category,status,owner,user_id) VALUES(?,?,?,?,?,?)');$q->execute([$name,$detail,$category,$status,$owner,uid()]); }
            redirect('index.php?ok=1');
        }
    } elseif ($action==='delete') {
        $id=filter_var($_POST['id'] ?? 0,FILTER_VALIDATE_INT) ?: 0;
        if ($id>0) {$q=$pdo->prepare('DELETE FROM records WHERE id=? AND user_id=?');$q->execute([$id,uid()]);}
        redirect('index.php?ok=2');
    }
}
$edit=null; $records=[];
if (auth()) {
    $editId=filter_var($_GET['edit'] ?? 0,FILTER_VALIDATE_INT) ?: 0;
    if ($editId) { $q=$pdo->prepare('SELECT * FROM records WHERE id=? AND user_id=?');$q->execute([$editId,uid()]);$edit=$q->fetch() ?: null; }
    $search=trim((string)($_GET['q'] ?? '')); $statusFilter=(string)($_GET['status'] ?? '');
    $sql='SELECT * FROM records WHERE user_id=?'; $args=[uid()];
    if ($search!=='') {$sql.=' AND (name LIKE ? OR detail LIKE ? OR owner LIKE ?)';$like='%'.str_replace(['%','_'],['\\%','\\_'],mb_substr($search,0,100)).'%';$args[]=$like;$args[]=$like;$args[]=$like;}
    if (in_array($statusFilter,STATUSES,true)) {$sql.=' AND status=?';$args[]=$statusFilter;}
    $sql.=' ORDER BY id DESC LIMIT 300';$q=$pdo->prepare($sql);$q->execute($args);$records=$q->fetchAll();
    $q=$pdo->prepare('SELECT COUNT(*) FROM records WHERE user_id=?'); $q->execute([uid()]); $count=(int)$q->fetchColumn();
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e(APP_TITLE)?> | DG Tech</title><link rel="stylesheet" href="style.css"></head><body>
<header><h1>DG Tech · <?=e(APP_TITLE)?></h1><?php if(auth()): ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="logout"><button class="secondary">Sair</button></form><?php endif ?></header>
<main>
<?php if (!auth()): ?>
<section class="card login"><h2>Acessar <?=e(APP_TITLE)?></h2><p class="muted">Escolha como deseja avaliar o projeto.</p>
<?php if($error): ?><p class="notice error"><?=e($error)?></p><?php endif ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="demo"><button type="submit">Entrar na demonstração (sem cadastro)</button></form>
<hr><h3>Já tenho conta</h3><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="login"><p><label>Usuário</label><input name="username" required autocomplete="username"></p><p><label>Senha</label><input type="password" name="password" required autocomplete="current-password"></p><button>Entrar</button></form>
<hr><h3>Criar conta de testes</h3><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="register"><p><label>Usuário</label><input name="username" minlength="3" maxlength="40" required autocomplete="username"></p><p><label>Senha (mínimo 12 caracteres)</label><input type="password" name="password" minlength="12" maxlength="128" required autocomplete="new-password"></p><button class="secondary">Cadastrar</button></form>
<p class="muted small">Cada usuário acessa apenas seus próprios registros. Não utilize dados pessoais reais neste ambiente demonstrativo.</p></section>
<?php else: ?>
<div class="top"><h2>Painel administrativo</h2><div class="stats"><span class="stat">Total de registros: <strong><?=e($count)?></strong></span></div></div>
<?php if($error): ?><p class="notice error"><?=e($error)?></p><?php endif ?><?php if($notice || isset($_GET['ok'])): ?><p class="notice"><?=e($notice ?: 'Operação concluída.')?></p><?php endif ?>

<section class="card"><h2><?= $edit ? 'Editar registro #'.e($edit['id']) : 'Novo registro' ?></h2><form method="post" class="grid"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($edit['id'] ?? 0)?>"><div><label for="name"><?=APP_TYPE==='tasks'?'Título da tarefa':'Equipamento / patrimônio'?></label><input id="name" name="name" maxlength="120" required value="<?=e($edit['name'] ?? '')?>"></div><div><label for="owner"><?=APP_TYPE==='tasks'?'Responsável':'Setor / responsável'?></label><input id="owner" name="owner" maxlength="120" value="<?=e($edit['owner'] ?? '')?>"></div><div><label for="category"><?=APP_TYPE==='tasks'?'Prioridade':'Tipo de equipamento'?></label><select id="category" name="category"><?php foreach(CATEGORIES as $v): ?><option value="<?=e($v)?>" <?=($edit['category'] ?? '')===$v?'selected':''?>><?=e($v)?></option><?php endforeach ?></select></div><div><label for="status">Status</label><select id="status" name="status"><?php foreach(STATUSES as $v): ?><option value="<?=e($v)?>" <?=($edit['status'] ?? '')===$v?'selected':''?>><?=e($v)?></option><?php endforeach ?></select></div><div class="wide"><label for="detail"><?=APP_TYPE==='tasks'?'Descrição':'Localização / observações'?></label><textarea id="detail" name="detail" maxlength="500"><?=e($edit['detail'] ?? '')?></textarea></div><div class="wide actions"><button><?= $edit?'Salvar alterações':'Cadastrar' ?></button><?php if($edit): ?><a class="btn secondary" href="index.php">Cancelar edição</a><?php endif ?></div></form></section>
<section class="card"><h2>Registros</h2><form class="filters" method="get"><div><label for="q">Pesquisar</label><input id="q" name="q" value="<?=e($search)?>" placeholder="Nome, descrição ou responsável"></div><div><label for="filter">Filtrar por status</label><select id="filter" name="status"><option value="">Todos</option><?php foreach(STATUSES as $v): ?><option value="<?=e($v)?>" <?=$statusFilter===$v?'selected':''?>><?=e($v)?></option><?php endforeach ?></select></div><button>Filtrar</button><a class="btn secondary" href="index.php">Limpar</a></form><div class="tablewrap"><table><thead><tr><th>ID</th><th><?=APP_TYPE==='tasks'?'Tarefa':'Equipamento'?></th><th><?=APP_TYPE==='tasks'?'Prioridade':'Tipo'?></th><th>Status</th><th>Responsável</th><th>Ações</th></tr></thead><tbody><?php foreach($records as $r): ?><tr><td><?=e($r['id'])?></td><td><strong><?=e($r['name'])?></strong><div class="muted small"><?=e($r['detail'])?></div></td><td><?=e($r['category'])?></td><td><span class="pill"><?=e($r['status'])?></span></td><td><?=e($r['owner'])?></td><td><div class="actions"><a class="btn secondary" href="?edit=<?=e($r['id'])?>">Editar</a><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($r['id'])?>"><button class="danger">Excluir</button></form></div></td></tr><?php endforeach ?><?php if(!$records): ?><tr><td colspan="6">Nenhum registro encontrado.</td></tr><?php endif ?></tbody></table></div><p class="muted small">Até 300 resultados por consulta. Dados armazenados em SQLite no servidor.</p></section>
<?php if(empty($_SESSION['demo'])): ?><section class="card"><h2>Alterar senha</h2><form method="post" class="grid"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="password"><div><label for="old">Senha atual</label><input id="old" name="old_password" type="password" required></div><div><label for="new">Nova senha (mínimo 12 caracteres)</label><input id="new" name="new_password" type="password" minlength="12" required></div><div class="wide"><button>Alterar senha</button></div></form></section><?php endif ?>
<?php endif ?></main><footer class="footer">DG Tech · Projeto demonstrativo PHP + SQLite</footer></body></html>
