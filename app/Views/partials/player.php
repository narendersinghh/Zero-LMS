<?php
/** Custom player shell shared by protected uploads (HTML5) and YouTube (IFrame API, controls hidden, click-through blocked). */
$wm = setting('video_watermark', true) ? $user['email'] . ' · #' . $user['id'] : '';
$progressUrl = url('/lessons/' . $lesson['id'] . '/progress');
?>
<div class="player" data-player data-kind="<?= $yt ? 'yt' : 'html5' ?>"<?= $yt ? ' data-source="' . e(url('/lessons/' . $lesson['id'] . '/source')) . '"' : ' data-src="' . e($playback) . '" data-mime="' . e($video['mime'] ?: 'video/mp4') . '"' ?> data-progress="<?= e($progressUrl) ?>" data-resume="<?= (int)($lp['position'] ?? 0) ?>" data-done="<?= $done ? 1 : 0 ?>">
<?php if ($yt): ?><div class="yt-host"><div data-yt-mount></div></div><div class="yt-cover" data-yt-cover></div>
<?php else: ?><video playsinline preload="metadata" controlslist="nodownload noremoteplayback noplaybackrate" disablepictureinpicture disableremoteplayback></video><?php endif; ?>
<div class="player-shield"></div>
<?php if ($wm): ?><div class="watermark" aria-hidden="true"><?= e($wm) ?></div><?php endif; ?>
<div class="player-loading" data-p="loading"><span class="spinner"></span></div>
<button type="button" class="player-big" data-p="play" aria-label="Play"><?= icon('play') ?></button>
<div class="player-bar">
<div class="pseek" data-p="seek" role="slider" aria-label="Seek" tabindex="0"><i class="pbuf"></i><i class="pfill"></i><i class="pknob"></i></div>
<div class="pctrls">
<button type="button" class="pbtn" data-p="toggle" aria-label="Play/Pause"><?= icon('play', 'i-play') ?><?= icon('pause', 'i-pause') ?></button>
<button type="button" class="pbtn" data-p="back" aria-label="Back 10 seconds"><?= icon('rewind') ?></button>
<button type="button" class="pbtn" data-p="fwd" aria-label="Forward 10 seconds"><?= icon('forward') ?></button>
<div class="pvol"><button type="button" class="pbtn" data-p="mute" aria-label="Mute"><?= icon('volume', 'i-vol') ?><?= icon('mute', 'i-mute') ?></button><input type="range" min="0" max="100" value="100" data-p="volume" aria-label="Volume"></div>
<span class="ptime" data-p="time">0:00 / 0:00</span>
<span class="grow"></span>
<?php if ($yt): ?><button type="button" class="pbtn pcc" data-p="cc" aria-label="Captions" aria-pressed="false">CC</button><?php endif; ?>
<div class="pset"><button type="button" class="pbtn" data-p="settings" aria-label="Quality and speed" aria-haspopup="true" aria-expanded="false"><?= icon('settings') ?><i class="pbadge" data-p="qbadge" hidden></i></button>
<div class="pmenu" data-p="menu" hidden><div class="pm-sec"><small>Quality</small><div class="pm-opts" data-p="quals"></div></div><div class="pm-sec"><small>Speed</small><div class="pm-opts" data-p="speeds"></div></div></div></div>
<button type="button" class="pbtn" data-p="fs" aria-label="Fullscreen"><?= icon('maximize') ?></button>
</div>
</div>
</div>
