$(document).ready(function () {
    $("#chooseFile").click(function () {

        elem = $("#file")[0];

        elem.click();
    });
    $("#chooseFile").change(function () {

        filename = $('input[type=file]')[0].files.length ? $('input[type=file]')[0].files[0].name : "";

        $("#fileName").val(filename);
    });

    $("#search_box").keyup(function () {

        concret_choosen = 0;

        search_text = $(this).val();

        url = $(this).attr('datasrc');

        $.post(url, {search: 1, search_text: search_text, concret_choosen: concret_choosen}, function (result) {

            getResultFields = [];

            result = JSON.parse(result);

            //console.log(result);

            searchResultsHtml = '<table id="resultsTable" class="table table-bordered"><thead><tr>';

            if (result.length > 0) {

                resultFirst = result[0];

                $.each(resultFirst, function (key, val) {

                    searchResultsHtml += '<th>' + key + '</th>';

                });
                searchResultsHtml += '<th><span class="choose">Choose</span></th>';

                searchResultsHtml += '</tr></thead><tbody>';

                $.each(result, function (key, resultCurr) {

                    searchResultsHtml += '<tr>';

                    $.each(resultCurr, function (key, val) {

                        searchResultsHtml += '<td>' + val + '</td>';

                    });
                    searchResultsHtml += '<td>' + '<button id="' + resultCurr.id + '" addresses_street_name="' + resultCurr.addresses_street_name + '" addresses_address="' + resultCurr.addresses_address + '" type="button" class="btn btn-success" onclick="choose(this)">Choose</button>' + '</td>';

                    searchResultsHtml += '</tr>';
                });
                searchResultsHtml += '</tbody></table>';
            }
            else {
                searchResultsHtml = 'No Results...';
            }
            $("#searchResults").html(searchResultsHtml);
        });
    });

});

function choose(self) {

    curr_post_id = self.id;

    concret_choosen = curr_post_id;

    url = $("#search_box").attr('datasrc');

    addresses_street_name = $(self).attr('addresses_street_name');

    addresses_address = $(self).attr('addresses_address');

    $("#search_box").val(addresses_street_name + " " + addresses_address);


    $.post(url, {search: 1, concret_choosen: concret_choosen}, function (result) {

        result = JSON.parse(result);

        srchDistancesLess = result.srchDistancesLess;

        srchDistancesMiddle = result.srchDistancesMiddle;

        srchDistancesMoreThan = result.srchDistancesMoreThan;

        createResponseHtml = '    <table id="distancesTable" class="table table-bordered">\n' +
            '        <thead>\n' +
            '        <tr>\n' +
            '            <th><span class="bold">Distance &lt; 5 Km</span></th>\n' +
            '            <th><span class="bold">Distance From 5 Km to 30</span> </th>\n' +
            '            <th><span class="bold">Distance more than 30 Km</span></th>\n' +
            '        </tr> </thead>';

        createResponseHtml += '<tbody>';


        if (result.maxCountDistanceType == "srchDistancesLess") {


            $.each(srchDistancesLess, function (key, val) {

                createResponseHtml += '<tr>';

                createResponseHtml += '<td>' + srchDistancesLess.full_street_addr_name + '( ' + srchDistancesLess.distance + ')' + '</td>';

                createResponseHtml += '<td>' + srchDistancesMiddle.full_street_addr_name + '( ' + srchDistancesMiddle.distance + ')' + '</td>';

                createResponseHtml += '<td>' + srchDistancesMoreThan.full_street_addr_name + '( ' + srchDistancesMoreThan.distance + ')' + '</td>';

                createResponseHtml += '</tr>';

            });


        }
        else if (result.maxCountDistanceType == "srchDistancesMiddle") {

            $.each(srchDistancesMiddle, function (key, val) {
                console.log(srchDistancesLess);

                createResponseHtml += '<tr>';

                if (srchDistancesLess[key]) {
                    createResponseHtml += '<td>' + srchDistancesLess[key].full_street_addr_name + '( ' + srchDistancesLess[key].distance + ')' + '</td>';

                }
                else {
                    createResponseHtml += '<td>' + '--' + '</td>';

                }

                createResponseHtml += '<td>' + val.full_street_addr_name + '( ' + val.distance + ')' + '</td>';



                if (srchDistancesMoreThan[key]) {
                    createResponseHtml += '<td>' + srchDistancesMoreThan[key].full_street_addr_name + '( ' + srchDistancesMoreThan[key].distance + ')' + '</td>';
                }
                else {
                    createResponseHtml += '<td>' + '--' + '</td>';

                }

                
                createResponseHtml += '</tr>';

            });

        }
        else {
            $.each(srchDistancesMoreThan, function (key, val) {

                createResponseHtml += '<tr>';

                createResponseHtml += '<td>' + srchDistancesLess.full_street_addr_name + '( ' + srchDistancesLess.distance + ')' + '</td>';

                createResponseHtml += '<td>' + srchDistancesMiddle.full_street_addr_name + '( ' + srchDistancesMiddle.distance + ')' + '</td>';

                createResponseHtml += '<td>' + srchDistancesMoreThan.full_street_addr_name + '( ' + srchDistancesMoreThan.distance + ')' + '</td>';

                createResponseHtml += '</tr>';

            });

        }

        createResponseHtml += '</tbody></table>';
        $("#distancesContent").html(createResponseHtml);


    });


    $("#search_box").keyup();
}




