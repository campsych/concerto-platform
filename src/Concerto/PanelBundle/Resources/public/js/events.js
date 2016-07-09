$(window).on("mouseup", function () {
    window.mouseDown = false;
});

$(window).keydown(function (event) {
    if (event.which == "17")
        window.cntrlIsPressed = true;
});

$(window).keyup(function () {
    window.cntrlIsPressed = false;
});