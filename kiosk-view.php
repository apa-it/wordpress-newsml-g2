<?php
require( 'kiosk-data.php' );
?>
<!doctype html>
<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script type="text/javascript">

        $(function () {

            var curIndex = 0;
            var URLs = $.map(<?php get_urls(); ?>, function (el) {
                return el;
            });
            var cache = {};
            var time = new Date().getTime();

            var pageTime = <?php get_time_per_page(); ?>;
            var postCount = <?php get_post_count(); ?>;

            function refresh() {
                console.log("in refresh");
                if (new Date().getTime() - time >= pageTime*1000 * postCount + 1000) { //equals 60 seconds
                    console.log("time: " + time);
                    window.location.reload(true);
                }
            }

            function switchNews() {

                var url = URLs[curIndex];
                var data = cache[url];

                curIndex += 1;
                if (curIndex == URLs.length) {
                    curIndex = 0;
                }

                if (!data) {
                    $("#main").load(url, function (data) {
                        cache[url] = data;
                        nextTimer();
                    })
                } else {
                    $("#main").html(data);
                    nextTimer();
                }
            }

            function nextTimer() {
                window.setTimeout(function () {
                    switchNews();
                    refresh();
                }, pageTime * 1000);
            }

            switchNews();
        });
    </script>
</head>
<body>
<section id="main"></section>
</body>
</html>