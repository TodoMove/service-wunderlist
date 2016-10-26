<?php
require __DIR__ . '/vendor/autoload.php';

$clientId = '***REMOVED***';
$token = '***REMOVED***';
$writer = new TodoMove\Service\Wunderlist\Writer($clientId, $token);

$project = new \TodoMove\Intercessor\Project('Mah project ' . rand(10090, 99999));
$project2 = new \TodoMove\Intercessor\Project('Mah project ' . rand(10090, 99999));
$project3 = new \TodoMove\Intercessor\Project('Mah project ' . rand(10090, 99999));
$tags = new \TodoMove\Intercessor\Tags();
$tags->add(new \TodoMove\Intercessor\Tag('shopping'));
$tags->add(new \TodoMove\Intercessor\Tag('errands'));
$tags->add(new \TodoMove\Intercessor\Tag('lowenergy'));

$task = new \TodoMove\Intercessor\Task('Mah task ' . rand(10090, 99999));
$task->notes('My notes, my notes, my notes are on fire')->flagged(true)->project($project2);
$task->tags($tags);

$project2->task($task);

$folder = new \TodoMove\Intercessor\ProjectFolder('Folders so cool');
$folder->projects([
    $project,
    $project2,
    $project3
]);

var_dump($writer->syncProject($project));
var_dump($writer->syncProject($project2));
var_dump($writer->syncProject($project3));
var_dump($writer->syncTask($task));
var_dump($writer->syncFolder($folder));