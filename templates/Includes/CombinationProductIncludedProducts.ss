<% if IncludedProducts %>
<div id="IncludedProductsSection">
	<h2>Included are:</h2>
	<ul>
		<% loop IncludedProducts %>
		<li class="productItem $FirstLast item$Pos">
			<h3><a href="$Link">$Title</a></h3>
		</li>
		<% end_loop %>
	</ul>
</div>
<% end_if %>


