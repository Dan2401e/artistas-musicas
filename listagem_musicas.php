<?php
include('valida_sessao.php');
include('conexao.php');

$mensagem = null;
$tipo_msg = null;

function getYoutubeVideoId(string $url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
        '/youtube\.com\/embed\/([^\&\?\/]+)/',
        '/youtube\.com\/v\/([^\&\?\/]+)/',
        '/youtu\.be\/([^\&\?\/]+)/'
    ];

    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) {
            return $m[1];
        }
    }

    return '';
}

$where = "1=1";
$params = [];
$types = "";

if (!empty($_GET['artista'])) {
    $where .= " AND a.id = ?";
    $params[] = intval($_GET['artista']);
    $types .= "i";
}

if (!empty($_GET['genero'])) {
    $where .= " AND m.genero LIKE ?";
    $params[] = "%" . trim((string) $_GET['genero']) . "%";
    $types .= "s";
}

if (!empty($_GET['busca'])) {
    $where .= " AND (m.titulo LIKE ? OR m.album LIKE ? OR a.nome LIKE ?)";
    $busca = "%" . trim((string) $_GET['busca']) . "%";
    $params[] = $busca;
    $params[] = $busca;
    $params[] = $busca;
    $types .= "sss";
}

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT imagem_capa FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $arq = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($arq && !empty($arq['imagem_capa']) && file_exists($arq['imagem_capa'])) {
        unlink($arq['imagem_capa']);
    }

    $stmt = $conn->prepare("DELETE FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $tipo_msg = $stmt->execute() ? "success" : "error";
    $mensagem = $tipo_msg === "success" ? "Música excluída!" : "Erro: " . $stmt->error;
    $stmt->close();
}

$artistas = $conn->query("SELECT id, nome FROM artistas ORDER BY nome");
$generos = $conn->query("SELECT DISTINCT genero FROM musicas WHERE genero IS NOT NULL AND genero <> '' ORDER BY genero");

$sql = "SELECT m.*, a.nome AS artista_nome
        FROM musicas m
        JOIN artistas a ON m.artista_id = a.id
        WHERE {$where}
        ORDER BY a.nome, m.titulo";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $musicas = $stmt->get_result();
} else {
    $musicas = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Musical — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="page-header-bar">
        <h2>🎼 Biblioteca Musical</h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
            <a href="cadastro_musica.php" class="btn-primary">➕ Nova Música</a>
        </div>
    </div>

    <div class="filters">
        <form method="get" class="filter-form">
            <div class="filter-group">
                <div class="filter-item">
                    <label for="busca">Busca geral</label>
                    <input type="text" name="busca" id="busca"
                           value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>"
                           placeholder="Título, álbum ou artista…">
                </div>
                <div class="filter-item">
                    <label for="artista">Artista</label>
                    <select name="artista" id="artista">
                        <option value="">Todos os artistas</option>
                        <?php while ($a = $artistas->fetch_assoc()): ?>
                            <option value="<?php echo $a['id']; ?>"
                                <?php echo (isset($_GET['artista']) && $_GET['artista'] == $a['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="genero">Gênero</label>
                    <select name="genero" id="genero">
                        <option value="">Todos os gêneros</option>
                        <?php while ($g = $generos->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($g['genero']); ?>"
                                <?php echo (isset($_GET['genero']) && $_GET['genero'] == $g['genero']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['genero']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">🔍 Filtrar</button>
                <?php if (!empty($_GET)): ?>
                    <a href="listagem_musicas.php" class="btn-back">✖ Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $tipo_msg; ?>">
            <?php echo $tipo_msg === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="col-capa">Capa</th>
                    <th>Título</th>
                    <th>Artista</th>
                    <th>Álbum</th>
                    <th>Gênero</th>
                    <th class="col-youtube">YouTube</th>
                    <th class="col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($musicas->num_rows > 0): ?>
                    <?php while ($row = $musicas->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($row['imagem_capa']): ?>
                                    <img src="<?php echo htmlspecialchars($row['imagem_capa']); ?>"
                                         alt="Capa" class="table-thumbnail" loading="lazy">
                                <?php else: ?>
                                    <div class="no-image">Sem<br>capa</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($row['artista_nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['album']) ?: '—'; ?></td>
                            <td><?php echo htmlspecialchars($row['genero']) ?: '—'; ?></td>
                            <td>
                                <?php
                                $vid = getYoutubeVideoId($row['youtube_link'] ?? '');
                                if ($vid):
                                    $watch = "https://www.youtube.com/watch?v={$vid}";
                                ?>
                                    <a href="<?php echo $watch; ?>" target="_blank" rel="noopener noreferrer" class="btn-youtube">▶ Assistir</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="cadastro_musica.php?edit_id=<?php echo $row['id']; ?>" class="btn-edit">✏️ Editar</a>
                                    <a href="?delete_id=<?php echo $row['id']; ?><?php echo !empty($_GET) ? '&' . http_build_query($_GET) : ''; ?>"
                                       onclick="return confirm('Excluir <?php echo addslashes($row['titulo']); ?>?')"
                                       class="btn-delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-cell">
                            <?php echo !empty($_GET) ? 'Nenhuma música encontrada com esses filtros.' : 'Nenhuma música cadastrada ainda.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
