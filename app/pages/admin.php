<main id="admin" class="wrapper">

	<?php

	$admin = new AuthAdmin();

	if (isset($_GET["logout"])) {
		$admin->out();
	}
	if (isset($_POST["name"], $_POST["passwd"])) {
		if (!$admin->in($_POST["name"], $_POST["passwd"])) {
			echo "<p class='error mtb fsb tac'>Špatně zadané údaje</p>";
		}
	}

	if ($admin->is_logged()): ?>
		<p class="tar"><a href="?page=admin&logout" class="button">Odhlásit</a></p>
		<h2>Rezervace ke schválení</h2>
		<div id="unapproved-reservations" class="reservation-list"></div>

		<h2>Schválené rezervace</h2>
		<div id="approved-reservations" class="reservation-list"></div>

		<h2>Přidat účel rezervace</h2>
		<form method="post" id="add-purpose">
			<ul class="table cols-3">
				<li class="tar pr">
					<input type="text" name="purpose" placeholder="Popis"/>
				</li>
				<li class="tac" data-column="price">
					<input type="text" name="discount" placeholder="Sleva" class="mrs"/>%
				</li>
				<li class="tal pl">
					<button type="submit">Uložit</button>
				</li>
			</ul>
		</form>

		<h2>Správa položek</h2>
		<div id="items"></div>
	<?php else: ?>
		<form action="?page=admin" method="post" class="tac">
			<fieldset>
				<legend>Přihlaste se</legend>
				<ul class="no-style">
					<li>
						<input type="text" name="name" placeholder="Jméno"/>
					</li>
					<li>
						<input type="password" name="passwd" placeholder="Heslo"/>
					</li>
				</ul>
				<p><button type="submit">Přihlásit</button></p>
			</fieldset>
		</form>
	<?php endif; ?>
</main>