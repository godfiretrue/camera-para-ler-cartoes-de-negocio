<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function readContact(): array
{
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        respond(400, ['error' => 'O pedido deve conter um objeto JSON válido.']);
    }

    $contact = [];
    foreach (['name', 'company', 'phone', 'email'] as $field) {
        $value = $payload[$field] ?? '';
        if (!is_string($value)) {
            respond(422, ['error' => 'Todos os campos devem ser texto.']);
        }

        $contact[$field] = trim($value);
        if (mb_strlen($contact[$field]) > 255) {
            respond(422, ['error' => 'Cada campo pode ter no máximo 255 caracteres.']);
        }
    }

    if ($contact['name'] === '') {
        respond(422, ['error' => 'O nome é obrigatório.']);
    }

    if ($contact['email'] !== '' && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
        respond(422, ['error' => 'Introduza um endereço de e-mail válido.']);
    }

    return $contact;
}

try {
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl !== false && $databaseUrl !== 'https://ghahufdjbfqnqpsaikif.supabase.co') {
        $connection = parse_url($databaseUrl);
        if (!is_array($connection) || !isset($connection['host'], $connection['user'], $connection['pass'], $connection['path'])) {
            throw new RuntimeException('DATABASE_URL não contém os dados necessários para a ligação PostgreSQL.');
        }

        $databaseName = ltrim($connection['path'], '/');
        if ($databaseName === '') {
            throw new RuntimeException('DATABASE_URL não indica o nome da base de dados.');
        }

        $database = new PDO(
            sprintf(
                'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
                $connection['host'],
                $connection['port'] ?? 5432,
                $databaseName
            ),
            rawurldecode($connection['user']),
            rawurldecode($connection['pass'])
        );
        $driver = 'pgsql';
    } else {
        $storageDirectory = getenv('CONTACTOS_STORAGE_DIR');
        if ($storageDirectory === false || $storageDirectory === '') {
            $storageDirectory = sys_get_temp_dir() . '/contractos-contacts';
        }

        if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0770, true) && !is_dir($storageDirectory)) {
            throw new RuntimeException('Não foi possível criar a pasta de dados.');
        }

        $database = new PDO('sqlite:' . $storageDirectory . '/contacts.sqlite');
        $driver = 'sqlite';
    }

    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    if ($driver === 'sqlite') {
        $database->exec(
            "CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                company TEXT NOT NULL DEFAULT '',
                phone TEXT NOT NULL DEFAULT '',
                email TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }
} catch (Throwable $error) {
    error_log('Contact database initialization failed: ' . $error->getMessage());
    respond(500, ['error' => 'Não foi possível aceder à base de dados de contactos.']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $search = trim((string) ($_GET['q'] ?? ''));
        $searchOperator = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
        if ($search === '') {
            $statement = $database->query('SELECT id, name, company, phone, email FROM contacts ORDER BY LOWER(name), id DESC');
        } else {
            $statement = $database->prepare(
                "SELECT id, name, company, phone, email
                 FROM contacts
                 WHERE name {$searchOperator} :name_search OR company {$searchOperator} :company_search
                 ORDER BY LOWER(name), id DESC"
            );
            $pattern = '%' . $search . '%';
            $statement->execute(['name_search' => $pattern, 'company_search' => $pattern]);
        }

        respond(200, ['contacts' => $statement->fetchAll()]);
    }

    if ($method === 'POST') {
        $contact = readContact();
        $insertSql = 'INSERT INTO contacts (name, company, phone, email) VALUES (:name, :company, :phone, :email)';
        if ($driver === 'pgsql') {
            $insertSql .= ' RETURNING id';
        }
        $statement = $database->prepare($insertSql);
        $statement->execute($contact);
        $contact['id'] = (int) ($driver === 'pgsql' ? $statement->fetchColumn() : $database->lastInsertId());
        respond(201, ['contact' => $contact]);
    }

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        respond(400, ['error' => 'Identificador de contacto inválido.']);
    }

    if ($method === 'PUT') {
        $contact = readContact();
        $contact['id'] = $id;
        $statement = $database->prepare(
            'UPDATE contacts
             SET name = :name, company = :company, phone = :phone, email = :email, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $statement->execute($contact);
        if ($statement->rowCount() === 0) {
            $exists = $database->prepare('SELECT 1 FROM contacts WHERE id = :id');
            $exists->execute(['id' => $id]);
            if (!$exists->fetchColumn()) {
                respond(404, ['error' => 'Contacto não encontrado.']);
            }
        }

        respond(200, ['contact' => $contact]);
    }

    if ($method === 'DELETE') {
        $statement = $database->prepare('DELETE FROM contacts WHERE id = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() === 0) {
            respond(404, ['error' => 'Contacto não encontrado.']);
        }

        respond(200, ['message' => 'Contacto eliminado.']);
    }

    header('Allow: GET, POST, PUT, DELETE');
    respond(405, ['error' => 'Método não permitido.']);
} catch (Throwable $error) {
    error_log('Contact API request failed: ' . $error->getMessage());
    respond(500, ['error' => 'Não foi possível concluir a operação. Tente novamente.']);
}
