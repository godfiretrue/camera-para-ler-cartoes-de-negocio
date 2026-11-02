<?php
declare(strict_types=1);

$htmlPath = dirname(__DIR__) . '/index.html';
$html = file_get_contents($htmlPath);
if ($html === false) {
    fwrite(STDERR, "Não foi possível ler index.html.\n");
    exit(1);
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);
$checks = [];

$check = static function (string $name, bool $passed) use (&$checks): void {
    $checks[] = [$name, $passed];
};

$check('Documento declara português de Portugal', $xpath->query('//html[@lang="pt-PT"]')->length > 0);
$check('Formulário explica campos opcionais e valor predefinido', $xpath->query('//p[@class="form-instructions"]')->length > 0);
$check('Nome tem etiqueta visível e erro associado', $xpath->query('//label[@for="field-name"]')->length > 0
    && $xpath->query('//input[@id="field-name"][@aria-describedby="name-error"]')->length > 0);
$check('E-mail tem instrução e erro associados', $xpath->query('//input[@id="field-email"][@aria-describedby="email-hint email-error"]')->length > 0);
$check('Leitura OCR anuncia o estado', $xpath->query('//*[@id="scan-status"][@role="status"]')->length > 0);
$check('Seletor de fotografia oculto não recebe foco invisível', $xpath->query('//input[@id="card-image"][@hidden]')->length > 0);
$check('Controlos que abrem o formulário expõem o estado', $xpath->query('//*[@aria-controls="editor"][@aria-expanded="false"]')->length === 2);
$check('A lista tem um destino programático para devolver o foco', $xpath->query('//h2[@id="directory-title"][@tabindex="-1"]')->length > 0);
$check('Ações dos contactos têm texto visível criado como texto', str_contains($html, 'text.textContent = visibleLabel;'));
$check('Campos inválidos expõem erro associado e estado inválido', str_contains($html, 'input.setAttribute("aria-invalid", "true")'));
$check('Ações principais mantêm-se numa coluna em ecrãs estreitos', str_contains($html, '.hero-actions .button { width: 100%;'));
$check('A leitura de cartões usa Tesseract.js em quatro passagens', str_contains($html, 'Tesseract.createWorker("por"')
    && str_contains($html, 'ocrWorker.recognize(preparedImages[imageIndex])')
    && str_contains($html, 'passResults.length + 1')
    && str_contains($html, 'confidence >= 60'));
$check('A fotografia é preparada em versões com contraste e limiarização', str_contains($html, 'prepareCardImages(file)')
    && str_contains($html, 'context.getImageData')
    && str_contains($html, 'maxVariance'));
$check('A seleção de campos considera resultados repetidos entre passagens', str_contains($html, 'function combineRecognizedFields(results)')
    && str_contains($html, 'candidate.support++')
    && str_contains($html, 'Math.min(15, (second.support - 1) * 5)'));
$check('A câmara prefere a resolução elevada e a câmara traseira', str_contains($html, 'width: { ideal: 1920 }')
    && str_contains($html, 'facingMode: { ideal: "environment" }'));

$color = static function (string $pattern) use ($html): string {
    if (!preg_match($pattern, $html, $matches)) {
        throw new RuntimeException('Não foi possível localizar uma cor CSS para medir contraste.');
    }

    return $matches[1];
};

$luminance = static function (string $hex): float {
    $channels = [];
    foreach ([1, 3, 5] as $offset) {
        $value = hexdec(substr($hex, $offset, 2)) / 255;
        $channels[] = $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }

    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
};

$contrast = static function (string $foreground, string $background) use ($luminance): float {
    $first = $luminance($foreground);
    $second = $luminance($background);

    return (max($first, $second) + 0.05) / (min($first, $second) + 0.05);
};

$muted = $color('/--muted:\s*(#[a-f0-9]{6})/i');
$green = $color('/--green:\s*(#[a-f0-9]{6})/i');
$placeholder = $color('/\.field input::placeholder,\s*\.search input::placeholder\s*\{\s*color:\s*(#[a-f0-9]{6})/i');
$focus = $color('/button:focus-visible[^}]*\{\s*outline:\s*3px solid\s*(#[a-f0-9]{6})/is');
$controlBorder = $color('/\.field input,\s*\.field select,\s*\.search input,\s*\.type-filter select\s*\{[^}]*border:\s*1px solid\s*(#[a-f0-9]{6})/is');

$check('Texto auxiliar cumpre 4.5:1 em fundo branco', $contrast($muted, '#ffffff') >= 4.5);
$check('Texto auxiliar cumpre 4.5:1 no fundo principal', $contrast($muted, '#f5f6f2') >= 4.5);
$check('Texto de exemplo cumpre 4.5:1 em fundo branco', $contrast($placeholder, '#ffffff') >= 4.5);
$check('Texto de exemplo cumpre 4.5:1 no fundo da pesquisa', $contrast($placeholder, '#fafbf9') >= 4.5);
$check('Indicador de foco cumpre 3:1 em fundo branco', $contrast($focus, '#ffffff') >= 3);
$check('Indicador de foco cumpre 3:1 no fundo principal', $contrast($focus, '#f5f6f2') >= 3);
$check('Limite dos campos cumpre 3:1 em fundo branco', $contrast($controlBorder, '#ffffff') >= 3);
$check('Limite dos campos cumpre 3:1 no fundo da pesquisa', $contrast($controlBorder, '#fafbf9') >= 3);
$check('Ligações verdes cumprem 4.5:1 em fundo branco', $contrast($green, '#ffffff') >= 4.5);

$failed = false;
foreach ($checks as [$name, $passed]) {
    printf("%s %s\n", $passed ? 'PASS' : 'FAIL', $name);
    $failed = $failed || !$passed;
}

printf("%d verificações; %d falhas.\n", count($checks), count(array_filter($checks, static fn (array $result): bool => !$result[1])));
exit($failed ? 1 : 0);
