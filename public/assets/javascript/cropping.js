function getRoundedCanvas(sourceCanvas) {
    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');
    var width = sourceCanvas.width;
    var height = sourceCanvas.height;

    canvas.width = width;
    canvas.height = height;
    context.imageSmoothingEnabled = true;
    context.drawImage(sourceCanvas, 0, 0, width, height);
    context.globalCompositeOperation = 'destination-in';
    context.beginPath();
    context.arc(width / 2, height / 2, Math.min(width, height) / 2, 0, 2 * Math.PI, true);
    context.fill();
    return canvas;
}

var input     = document.getElementById('form_avatar')
var container = document.getElementById('img-container')
var cropImage = document.getElementById('img-cropped')
var cropped    = document.getElementById('form_hidden')

input.addEventListener('change', function (e) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader()
    reader.onload = function (e) {
        container.style = 'display: block'
        cropImage.setAttribute('src', e.target.result)

        function update() {
            var croppedCanvas = cropper.getCroppedCanvas()
            var roundedCanvas = getRoundedCanvas(croppedCanvas)
            cropped.value = roundedCanvas.toDataURL()
            input.files = undefined
        }
        var cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            cropend: update,
            ready: update,
        })
    }
    reader.readAsDataURL(input.files[0])
})

