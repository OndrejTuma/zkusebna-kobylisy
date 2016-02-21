<main id="admin" class="wrapper">
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
</main>