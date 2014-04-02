<% if IncludedProducts %>
<div id="IncludedProductsSection">
	<h2>Included are:</h2>
	<ul>
		<% with/loop IncludedProducts %>
		<li class="productItem $FirstLast item$Pos">
			<h3><a href="$Link">$Title</a></h3>
		</li>
		<% end_with/loop %>
	</ul>
</div>
<% end_if %>


