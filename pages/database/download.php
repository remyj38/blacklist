<?php include 'html/header.php'; ?>
<form method="GET" action="<?php echo \Config::BASE_URL . '/database';?>" id="downloadForm">
    <div class="form-group">
        <label for="format">Database format</label>
        <select class="form-control" name="format" id="format" onchange="updateDownloadLink()">
            <?php
            foreach(array_keys(\Blacklist\Database::FORMATS) as $format) {
                echo "<option>" . $format . "</option>";
            }
            ?>
        </select>
    </div>
    <div class="form-group form-check">
        <input type="checkbox" class="form-check-input" id="include_expired" name="include_expired" onclick="updateDownloadLink()">
        <label class="form-check-label" for="include_expired">Include expired entries</label>
    </div>
    <input name="token" id="token" value="<?php echo \Config::DOWNLOAD_PASSWORD; ?>" type="hidden" />
    <p>Download link : <a id="downloadLink" href=""></a></p>
    <button type="submit" class="btn btn-primary">Download</button>
</form>
<script type="text/javascript">
function updateDownloadLink() {
    url = $('#downloadForm').attr('action') + '?format=' + $('#format').val() + '&token=' + $('#token').val();
    if ($('#include_expired').prop('checked')) {
        url += '&include_expired';
    }
    $('#downloadLink').attr('href', url).text(url);
}
window.onload = function() {
    updateDownloadLink()
}
</script>
<?php include 'html/footer.php'; ?>
