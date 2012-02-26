<% if Components %>
<div id="ComponentSection">
	<h2>Included are:</h2>
	<ul>
		<% control Components %>
		<% include ProductGroupItem %>
		<% end_control %>
	</ul>
</div>
<% end_if %>


