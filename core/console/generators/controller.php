/**
 * Controlador <?= $class . PHP_EOL ?>
 * 
 * @category App
 * @package Controllers
 */
class <?= $class ?>Controller extends <?= $parent ?>

{
<?php if ($parent === 'ScaffoldController'): ?>
    public string $model = '<?= Util::smallcase($class) ?>';
<?php endif; ?>
}
