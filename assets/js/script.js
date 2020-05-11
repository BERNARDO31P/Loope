let body = $("body"),
    header = $("#header"),
    main = $("#main"),
    search = $("#search"),
    searchbar = $("#searchbar"),
    logo = $("#logo"),
    cleaner = $("#cleaner"),
    suggestions = $("#suggestions"),
    buttons = $("#buttons");

// Implementierung einer Funktionseigenschaft
Object.defineProperty(Array.prototype, 'chunk_inefficient', {
    value: function (chunkSize) {
        let array = this;
        return [].concat.apply([],
            array.map(function (elem, i) {
                return i % chunkSize ? [] : [array.slice(i, i + chunkSize)];
            })
        );
    }
});

// Diese Funktion ersetzt die URL mit den nötigen GET Parameter
function insertParams(keys, values) {
    let params = "?" + keys[0] + "=" + values[0];
    for (let i = 1; i < keys.length; i++) {
        params += "&" + keys[i] + "=" + values[i];
    }
    history.pushState(history.state, window.title, pageUrl + params);
}

// Diese Funktion konvertiert den Unix Zeitstempel um in ein leserliches Datum
function timeConverter(dateP) {
    let dateN = Math.round(+new Date() / 1000), diffS = dateN - dateP;

    if (diffS < 60 && diffS > 1) {
        return "vor " + diffS + " Sekunden";
    } else if (diffS === 1) {
        return "vor " + diffS + " Sekunde";
    }

    let diffM = Math.round(diffS / 60);
    if (diffM < 60 && diffM > 1) {
        return "vor " + diffM + " Minuten";
    } else if (diffM === 1) {
        return "vor " + diffM + " Minute";
    }

    let diffH = Math.round(diffM / 60);
    if (diffH < 24 && diffH > 1) {
        return "vor " + diffH + " Stunden";
    } else if (diffH === 1) {
        return "vor " + diffH + " Stunde";
    }

    let diffD = Math.round(diffH / 24);
    if (diffD < 7 && diffD > 1) {
        return "vor " + diffD + " Tagen";
    } else if (diffD === 1) {
        return "vor " + diffD + " Tag";
    }

    let a = new Date(dateP * 1000),
        months = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    return a.getDate() + ". " + months[a.getMonth()] + " " + a.getFullYear();
}

// Diese Funktion ändert die CSS Datei
function changeCSS(cssFile) {
    let style = document.getElementById("style");
    style.setAttribute("href", cssFile);
}

// Diese Funktion sendet Vorschlaganfragen und verarbeitet diese
function sendSugg() {
    let value = search.val();

    if (value !== "" && !checkExists(value)) {
        $.ajax({
            method: "POST",
            url: "./assets/php/search.php",
            data: {
                "sugg": value
            },
            // Falls alles klappen sollte, wird die Datenliste ersetzt
            success: function (response) {
                let data = jQuery.parseJSON(JSON.stringify(response)), content = "";
                $.each(data['info'], function (value) {
                    content += "<option value='" + value + "'>";
                });
                suggestions.empty();
                suggestions.append(content);
            },
            error: function () {
                alert("Irgendetwas funktioniert nicht. Bitte laden Sie die Seite neu.");
            }
        });
    }
}

// Diese Funktion sendet Suchanfragen und verarbeitet diese
function sendSearch(page = 1) {
    let value = search.val(), start = new Date().getTime();

    if (value !== "") {
        $.ajax({
            method: "POST",
            url: "./assets/php/search.php",
            data: {
                "search": value,
                "page": page
            },
            /*
             * Falls alles klappen sollte wird die Seite etwas umstruckturiert, falls es noch nicht gemacht wurde
             *
             * Danach werden die Suchergebnisse formatiert und in #main eingefügt
             * Falls die Suchanfrage mehrere Seiten Ergebnisse liefert, wird eine Seiten Navigation generiert
             */
            success: function (response) {
                if (main.find("#searchbar").length > 0) {
                    changeCSS("./assets/css/results.css");
                    logo.appendTo("#header");
                    searchbar.appendTo("#header");

                    buttons.hide();
                    buttons.appendTo("#header");
                }
                main.empty();

                insertParams(["search", "page"], [value, page]);
                document.title = "Results - " + value;

                let data = jQuery.parseJSON(JSON.stringify(response));
                if (!data['error']) {
                    if (data !== null && (data['info']['files'] || data['info']['pictures'])) {
                        main.scrollTop(0);

                        // Verarbeitung von den Suchergebnissen
                        $.each(data['info']['files'], function (key, info) {
                            let object = "<div class='result'>" +
                                "<span class='dirname'>" + info['dirname'] + "</span><br/>" +
                                "<a target='_blank' href='./assets/php/download.php?dir=" + info['dirname'] + "&file=" + info['basename'] + "' class='basename'>" + info['basename'] + "</a><br/>" +
                                "<span><span class='changed'>" + timeConverter(info['lastChange']) + "</span> - " + info['content'] + "</span>";

                            if (info['notFound']) {
                                object += "<br/><span class='notfound'>Es fehlt: ";
                                $.each(info['notFound'], function (key, value) {
                                    object += "<del>" + value + "</del> ";
                                });
                                object += " | Muss enthalten: ";
                                $.each(info['notFound'], function (key, value) {
                                    object += "<a href='#' class='mustcontain'>" + value + "</a> ";
                                });
                                object += "</span>";
                            }

                            object += "</div>";

                            $(object).appendTo("#main");
                        });

                        // Verarbeitung von eventuell Bilder
                        if (page === 1) {
                            let object = '<div id="imageview">';

                            $.each(data['info']['pictures'].chunk_inefficient(20)[0], function (key, info) {
                                let url = "./assets/php/download.php?dir=" + info['dirname'] + "&file=" + info['basename'];
                                object += "<a href='" + url + "'><img class='images' src='" + url + "' alt='" + info['filename'] + "'/></a>";
                            })

                            object += "</div>";
                            $(object).prependTo("#main");
                        }

                        // Verarbeitung der Navigation
                        if (data['pages'] > 1) {

                            let object = "<div class='page'><span>L</span>";

                            for (let i = 0; i < data['pages']; i++) {
                                object += "<div class='pagenum'>";
                                if (i + 1 === page) {
                                    object += "<span class='active'>o</span>";
                                } else {
                                    object += "<span>o</span>";
                                }
                                object += "<span class='nums'>" + (i + 1) + "</span></div>";
                            }

                            object += "<span>p</span><span>é</span></div>";
                            $(object).appendTo("#main");
                        }
                    } else {
                        $("<div class='result'><span class='filecontent'>Leider wurden keine Ergebnisse zur Suchanfrage gefunden.</span></div>").appendTo("#main");
                    }
                    main.prepend("<span class='info'>" + data['results'] + " Ergebnisse in " + (new Date().getTime() - start) / 1000 + " Sekunden</span>");
                } else {
                    alert(data['error']);
                }
            },
            error: function () {
                alert("Irgendetwas funktioniert nicht. Bitte laden Sie die Seite neu.");
            }
        });
    }
}

// Diese Funktion dient zur Verarbeitung der GET Parameter
function getSearch(query, page) {
    search.val(query);
    cleaner.show();
    sendSearch(page);
}

// Überprüft ob die Eingabe mit den Vorschlägen übereinstimmt
function checkExists(inputValue) {
    let x = document.getElementById("suggestions"), i, flag;

    for (i = 0; i < x.options.length; i++) {
        if (inputValue === x.options[i].value) {
            flag = true;
        }
    }
    return flag;
}

body.on("click", ".mustcontain", function (e) {
    e.preventDefault();
    let word = e.target.text, searchval = search.val();
    searchval = searchval.replace(word, '"' + word + '"')
    search.val(searchval);
    sendSearch();
});

// Verwaltung von den Vorschlägen und das Kreuz im Suchfeld
body.on("input", "#search", function () {
    let value = search.val();
    if (value === "") {
        cleaner.hide();
        suggestions.empty();
    } else {
        cleaner.show();
        sendSugg();
    }
});

// Anzeigen von Vorschlägen beim anklicken vom Suchfeld
body.on("click", "#search", function () {
    sendSugg();
})

// Wenn das Kreuz angeklickt wird, wird die Hauptseite geladen
body.on("click", "#cleaner", function () {
    if (header.find("#searchbar").length > 0) {
        main.empty();
        changeCSS("./assets/css/search.css");

        logo.appendTo("#main");
        searchbar.appendTo("#main");

        buttons.appendTo("#main");
        buttons.show();
    }
    history.pushState(history.state, window.title, pageUrl);
    document.title = "Search";
    search.val("");
    search.focus();
    suggestions.empty();
    cleaner.hide();
});

// Erkennung von der Entertaste um zu Suchen
search.keypress(function (e) {
    if (e.key === "Enter") {
        sendSearch();
        search.blur();
        return false;
    }
});

body.on("click", "#luckybutton", function (e) {
    e.preventDefault();
    let value = search.val();

    if (value !== "") {
        $.ajax({
            method: "POST",
            url: "./assets/php/search.php",
            data: {
                "search": value
            },
            success: function (response) {

                let data = jQuery.parseJSON(JSON.stringify(response));
                if (!data['error']) {
                    let link = document.createElement("a");
                    link.download = data['info']['basename'];
                    link.href = data['info']['url'];
                    link.target = "_blank";
                    link.click();
                    link.remove();
                } else {
                    alert(data['error']);
                }
            },
            error: function () {
                alert("Irgendetwas funktioniert nicht. Bitte laden Sie die Seite neu.");
            }
        });
    }
})

// Wenn die Lupe angeklickt wird, wird auch gesucht
body.on("click", ".fa-search, #searchbutton", function (e) {
    e.preventDefault();
    sendSearch();
});

// Verwaltung von der Seiten Navigation
body.on("click", ".pagenum", function () {
    let page = $(this).children("span")[1].innerText;
    sendSearch(page);
});