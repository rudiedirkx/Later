<?php

$_list = @$groups ?: $bookmarks;

echo '<ol class="bookmarks ' . ($inner ? 'inner' : 'outer') . '">';
foreach ( $_list as $g => $bm ) {
	if ( is_array($bm) ) {
		$hidden = !$filter && in_array($g, $user->hide_groups) ? 'hidden' : '';
		echo '<li class="multiple ' . $hidden . '" data-group="' . html($g) . '">';
		$groupTotal = isset($groupTotals[$g]) ? ' (' . (count($bm) < $groupTotals[$g] ? count($bm) . ' / ' : '') . $groupTotals[$g] . ')' : '';
		$filterLink = '<a class="filter" href="?group_filter=' . urlencode($g) . '">filter</a>';
		$name = '<a href="' . get_url('index', array(
			'group' => $g,
			'hidden' => '__HIDDEN__',
			'_token' => get_token('toggleGroup'),
		)) . '" class="group-name">' . $g . '</a>';
		echo '<div class="group-header">' . $name . $groupTotal . ' (' . $filterLink . ')</div>';

		$inner = true;
		$groups = $bm;
		require 'tpl.bookmarks.php';
		$inner = false;
		echo '</li>';

		continue;
	}

	$id = $bm->id;

	$_url = parse_url($bm->url);
	$host = preg_replace('#^www\.#', '', @$_url['host'] ?: 'invalid url');

	$classes = array('single');
	$bm->favorite && $classes[] = 'is-favorite';

	$archiveAction = $bm->archive ? 'unarchive' : 'archive';

	echo '<li data-id="' . $bm->id . '" data-selected-group="' . $bm->group . '" class="' . implode(' ', $classes) . '">';
	echo '<div class="' . $archiveAction . '"><a class="ajax" href="' . get_url('index', array(
		$archiveAction => $id,
		'_token' => get_token($archiveAction),
	)) . '">A</a></div>';
	echo '<div class="link">';
	echo '  <a href="' . html($bm->url) . '" rel="noreferrer">' . html($bm->title ?: $bm->url) . '</a>';
	echo '  <div class="favorite"><a class="ajax" href="' . get_url('index', array(
		'favorite' => $id,
		'value' => (int)!$bm->favorite,
		'_token' => get_token('favorite'),
	)) . '">♥</a></div>';
	echo '</div>';
	echo '<div class="created">' . date(DT, $bm->created) . '</div>';
	// if ( $bm->group ) {
		// echo '<div class="group">[' . $bm->group . ']</div>';
	// }
	if (LATER_READABILITY_PARSER_API_TOKEN) {
		echo '<div class="host"><a href="' . get_url('read', array('id' => $id)) . '">' . $host . '</a></div>';
	}
	else {
		echo '<div class="host">' . $host . '</div>';
	}
	if ( @$groupOptions ) {
		echo '<div class="change-group"><select name="group_' . $id . '"><option value>-</option>' . html_options($groupOptions, $bm->group) . '</select></div>';
	}
	echo '<div class="edit"><a href="' . get_url('form', array('id' => $id)) . '">E</a></div>';
	if ( !$bm->archive ) {
		echo '<div class="actualize"><a class="ajax" href="' . get_url('index', array(
			'actualize' => $id,
			'_token' => get_token('actualize'),
		)) . '">▲</a></div>';
	}
	echo '</li>';
}
echo '</ol>';

// No footer for sub-inclusions
if ( @$inner ) {
	return;
}

$pages = ceil($total / $limit);

?>
<?if ($pages > 1): ?>
	<ul class="pager has-<?= $pages ?>-pages">
		<? foreach (range(0, $pages-1) as $p): ?>
			<li class="<?= $p == $page ? 'current' : '' ?>">
				<a href="?page=<?= $p ?>"><?= $p+1 ?></a>
			</li>
		<? endforeach ?>
	</ul>
<? endif ?>

<script>
function rAjax(href, done) {
	var xhr = new XMLHttpRequest;
	xhr.onload = function(e) {
		if ( this.status == 200 && this.responseText == 'OK' ) {
			return (done || function() {
				location.reload();
			})();
		}

		alert('Error: ' + this.responseText);
		document.querySelector('.working').classList.remove('working');
	};
	xhr.open('get', href, true);
	xhr.send();
}
[].forEach.call(document.querySelectorAll('a.ajax'), function(el) {
	el.addEventListener('click', function(e) {
		e.preventDefault();
		this.classList.add('working');
		rAjax(el.href);
	});
});

[].forEach.call(document.querySelectorAll('.change-group select'), function(el) {
	el.addEventListener('change', function(e) {
		var group = this.value || '';
		var id = this.parentNode.parentNode.getAttribute('data-id');
		var base = '<?= get_url('index', array(
			'group' => '__GROUP__',
			'id' => '__ID__',
			'_token' => get_token('group'),
		)) ?>';
		var href = base.replace('__GROUP__', encodeURIComponent(group)).replace('__ID__', id);
		this.classList.add('working');
		rAjax(href);
	});
});

[].forEach.call(document.querySelectorAll('a.group-name'), function(el) {
	el.addEventListener('click', function(e) {
		e.preventDefault();
		var group = this.parentNode.parentNode,
			hidden = group.classList.toggle('hidden'),
			base = this.href,
			href = base.replace('__HIDDEN__', Number(hidden));
		rAjax(href, new Function);
	});
});
</script>
