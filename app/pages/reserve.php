<main id="reserve" class="wrapper">

	<a class="button" href="<?= ZKUSEBNA_APACHE_ROOT_URL ?>">Zpět</a>

	<form action="" method="post" id="form__reserve">
		<fieldset class="date">
			<legend>Zvolte termín vypůjčení</legend>
			<ul class="table cols-2">
				<li class="tar pr">
					<input type="text" name="date_from" id="date_from" readonly placeholder="Datum a čas výpůjčky" value="10.02.2016 08:00"/>
				</li>
				<li class="pt tal">
					<input type="text" name="date_to" id="date_to" readonly placeholder="Datum a čas navrácení" value="11.02.2016 12:00"/>
				</li>
			</ul>
		</fieldset>
		<fieldset class="contact">
			<legend>Vyplňte kontaktní údaje</legend>
			<ul class="table cols-4">
				<li class="tar">
					<input type="text" name="name" id="name" placeholder="Celé jméno" value="Ondra"/>
				</li>
				<li class="tac">
					<input type="text" name="phone" id="phone" placeholder="Telefon" value="123123123"/>
				</li>
				<li class="tal">
					<input type="email" name="email" id="email" placeholder="Email" value="ondr@centrum.cz"/>
				</li>
			</ul>
		</fieldset>
	</form>
	<h1>Přehled</h1>

	<div id="reserved-items-wrapper" class="empty">
		<div id="reserved-items"></div>
	</div>
	<div id="items-wrapper"></div>
</main>