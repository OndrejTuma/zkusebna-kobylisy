<?php
$admin = new AuthAdmin();


?>

<main id="reserve" class="wrapper">

	<?php if($admin->is_logged()): ?>
	<a class="button" href="?page=admin">Zpět</a>
	<p class="fr"><a href="?page=admin&logout" class="button--red">Odhlásit</a></p>
	<?php else: ?>
	<a class="button" href="<?= ZKUSEBNA_APACHE_ROOT_URL ?>">Zpět</a>
	<?php endif; ?>

	<form action="" method="post" id="form__reserve">
		<fieldset class="date">
			<legend>Zvolte termín vypůjčení</legend>
			<ul class="table cols-2 pt mbn">
				<li class="tar pr">
					<input type="text" name="date_from" id="date_from" class="datetimepicker" data-date-type="from" data-connected-to="#date_to" readonly placeholder="Datum a čas výpůjčky" value="29.02.2016 08:00"/>
<!--					<input type="text" name="date_from" id="date_from" data-date-type="from" data-connected-to="#date_to" readonly placeholder="Datum a čas výpůjčky"/>-->
				</li>
				<li class="tal pl">
					<input type="text" name="date_to" id="date_to" class="datetimepicker" data-date-type="to" data-connected-to="#date_from" readonly placeholder="Datum a čas navrácení" value="29.02.2016 12:00"/>
<!--					<input type="text" name="date_to" id="date_to" data-date-type="to" data-connected-to="#date_from" readonly placeholder="Datum a čas navrácení"/>-->
				</li>
			</ul>
		</fieldset>
		<fieldset class="contact">
			<legend>Vyplňte kontaktní údaje</legend>
			<ul class="table cols-4 pt">
				<li class="tar prb">
					<input type="text" name="name" id="name" placeholder="Celé jméno" value="Ondra"/>
<!--					<input type="text" name="name" id="name" placeholder="Celé jméno"/>-->
				</li>
				<li class="tac">
					<input type="text" name="phone" id="phone" placeholder="Telefon" value="123123123"/>
<!--					<input type="text" name="phone" id="phone" placeholder="Telefon"/>-->
				</li>
				<li class="tac">
					<input type="email" name="email" id="email" placeholder="Email" value="ondr@centrum.cz"/>
<!--					<input type="email" name="email" id="email" placeholder="Email"/>-->
				</li>
				<li class="tal plb">
					<?php
						$items = new Items();
						print $items->getSelectPurpose();
					?>
				</li>
			</ul>
		</fieldset>
		<?php if($admin->is_logged()): ?>
			<fieldset class="repeat">
				<legend>Opakování</legend>
				<ul class="table cols-2 pt">
					<li class="tar prb">
						<select name="repeat_type" id="repeat">
							<option disabled selected>Vyberte četnost</option>
							<option value="weekly">Jednou týdně</option>
							<option value="weekly2">Jednou za 14 dní</option>
							<option value="monthly">Jednou za měsíc</option>
						</select>
					</li>
					<!--li class="tac">
						<input type="text" name="repeat_from" id="repeat_from" class="datetimepicker" data-type="date" data-date-type="from" data-connected-to="#repeat_to" readonly placeholder="Opakovat od" value="29.02.2016"/>
						<input type="text" name="repeat_from" id="repeat_from" data-date-type="from" data-connected-to="#repeat_to" placeholder="Opakovat od"/>
					</li-->
					<li class="tal plb">
						<input type="text" name="repeat_to" id="repeat_to" class="datetimepicker" data-type="date" readonly placeholder="Opakovat do" value="29.04.2016"/>
<!--						<input type="text" name="repeat_to" id="repeat_to" class="datetimepicker" data-type="date" data-date-type="to" data-connected-to="#repeat_from" readonly placeholder="Opakovat do" value="29.04.2016"/>-->
<!--						<input type="text" name="repeat_to" id="repeat_to" data-date-type="to" data-connected-to="#repeat_from" placeholder="Opakovat do"/>-->
					</li>
				</ul>
			</fieldset>
		<?php endif; ?>
	</form>
	<h1>Přehled</h1>

	<div id="reserved-items-wrapper" class="empty">
		<div id="reserved-items"></div>
	</div>
	<div id="items-wrapper"></div>
</main>