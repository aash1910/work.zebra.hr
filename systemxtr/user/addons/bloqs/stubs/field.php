{<?=$field_name?>}

<?php foreach ($bloqs as $bloqShortName => $atoms) : ?>
    <?php if($show_comments ?? false): ?>

        {!-- Bloq: <?=$bloqShortName?> --}
    <?php endif; ?>

    {<?=$bloqShortName?>}

    <?php foreach ($atoms['atoms'] as $atomName => $atom) : ?>
        <?php if($show_comments ?? false): ?>

            {!-- Atom: <?=$atom['field_label']?> --}
            {!-- Fieldtype: <?=$atom['field_type']?> --}
            {!-- Docs: <?=$atom['docs_url']?> --}
        <?php endif; ?>

        <?=$this->embed($atom['stub'], $atom)?>

        <?php if($show_comments ?? false): ?>

            {!-- End Atom: <?=$atom['field_label']?> --}
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($atoms['canHaveChildren']): ?>
        <?php if($show_comments ?? false): ?>

            {!-- This bloq is allowed to have nested child bloqs. This tag will be replaced with it's children. --}
        <?php endif; ?>

        <div>
            {bloqs:children}
        </div>
    <?php endif; ?>

    {/<?=$bloqShortName?>}

    <?php if($show_comments ?? false): ?>

        {!-- End Bloq: <?=$bloqShortName?> --}
    <?php endif; ?>
<?php endforeach; ?>

{/<?=$field_name?>}
