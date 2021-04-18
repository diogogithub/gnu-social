var input     = document.getElementById('save_avatar');
var container = document.getElementById('img-container');
var cropImage = document.getElementById('img-cropped');
var cropped   = document.getElementById('save_hidden');

input.addEventListener('change', function (e) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader()
    reader.onload = function (e) {
        container.style = 'display: block';
        cropImage.setAttribute('src', e.target.result);

        function update() {
            var croppedCanvas = cropper.getCroppedCanvas();
            cropped.value = croppedCanvas.toDataURL();
            input.files = undefined;
        }
        var cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            cropend: update,
            ready: update,
        })
    }
    reader.readAsDataURL(input.files[0]);
});
