<?php

/**
 * This file contains the cancellation form site type
 *
 * @var QUI\Projects\Project $Project
 * @var QUI\Projects\Site $Site
 * @var QUI\Interfaces\Template\EngineInterface $Engine
 * @var QUI\Template $Template
 */

$Site->setAttribute('nocache', true);

$CancellationForm = new QUI\ERP\Order\CancellationPolicy\Controls\CancellationForm();

$Engine->assign([
    'CancellationForm' => $CancellationForm
]);
