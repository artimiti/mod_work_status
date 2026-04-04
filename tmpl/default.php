<?php
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

$status = ModWorkStatusHelper::getStatus($params);
$useModal = (bool)$params->get('use_modal', 0);
$ending = trim($params->get('ending_text', ''));
$uniqueId = 'work-status-modal-' . $module->id;

// Если есть объявление — всегда простой вывод, без модалки
if ($status['is_announcement']) {
    echo '<div class="work-status ' . $status['class'] . '">' .
         htmlspecialchars($status['text'], ENT_QUOTES, 'UTF-8') .
         '</div>';
    return;
}

// Формируем текст для фронтенда (с "Окончанием", если нужно)
$frontendText = $status['text'];
if ($ending && $status['is_time_related']) {
    $frontendText .= ' ' . $ending;
}

if ($useModal): ?>
<!-- Триггер открытия модалки -->
<div class="work-status work-status--modal <?= $status['class']; ?>" uk-toggle="target: #<?= $uniqueId; ?>">
    <a class="uk-link-reset" href="#<?= $uniqueId; ?>" uk-scroll><?= htmlspecialchars($frontendText, ENT_QUOTES, 'UTF-8'); ?><span uk-icon="triangle-down"></span></a>
</div>

<!-- Модальное окно (без "Окончания"!) -->
<div id="<?= $uniqueId; ?>" uk-modal>
    <div class="uk-modal-dialog uk-modal-body uk-margin-auto uk-margin-auto-vertical uk-border-rounded">
        <button class="uk-modal-close-outside uk-close uk-icon" type="button" uk-close></button>
        <div class="uk-panel uk-margin-small"><strong>График работы</strong></div>
        <hr class="uk-margin-remove-vertical">
        <ul class="uk-list uk-list-large uk-margin-small">
            <?= ModWorkStatusHelper::getScheduleHtml($params); ?>
        </ul>
    </div>
</div>
<?php else: ?>
<!-- Простой вывод -->
<div class="work-status <?= $status['class']; ?>">
    <?= htmlspecialchars($frontendText, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>