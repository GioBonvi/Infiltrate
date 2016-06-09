<label for="language">Language:&nbsp;</label>
<select id="language-select" name="language">
    <option value="IT">Italiano</option>
    <option value="EN">English</option>
</select>
<script>
var name = "language=";
var ca = document.cookie.split(';');
for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') {
        c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
        $("#language-select").val(c.substring(name.length,c.length));
    }
}
$("#language-select").change(function() {
    var d = new Date();
    d.setTime(d.getTime() + (30*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = "language=" + $(this).val() + "; " + expires;
    location.reload();
});
</script>
