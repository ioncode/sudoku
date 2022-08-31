console.log('Sudoku client started');
var conn = new WebSocket('ws://localhost:8080');
//retrieve client name from session or generate new
if (!sessionStorage.getItem('clientName')) {
    populateClientName();
}
function populateClientName($value = 'User_'+navigator.appVersion.substr(-5)) {
    sessionStorage.setItem('clientName', $value);
    console.log(sessionStorage);
}
$(function() {
    console.log( "Document ready, let me run client side code" );
    $("form#client>#name").val(sessionStorage.getItem('clientName'));
});

conn.onmessage = function(e) {
    console.log('Response:' + e.data);
    // try process received data as json
    try {
        let data = '';
        data = e.data;
        data = JSON.parse(data);
        if (data.matrix){
            console.log('Matrix received:');
            $("#sudoku").empty();
            let matrix = Object.values(data.matrix);
            matrix.forEach((column, colIndex) =>{
                //colIndex++;
                column = Object.values(column);
                column.forEach((cell, rowIndex) =>{
                        //rowIndex++;
                        //console.log('Processing cell ['+colIndex+':'+rowIndex+'] with value '+cell);
                        if (cell){
                            $("#sudoku").append('<li class="ui-state-disabled">'+cell+'</li>');
                        }
                        else {
                            $("#sudoku").append('<li data-column='+colIndex+' data-row='+rowIndex+' class="cancel ui-state-default" title="Click to set value"></li>');




                        }
                    }
                );
            });

            //process active cells
            $( "li.ui-state-default" ).hover(function() {
                console.log(this);
                $(this).append('<ol></ol>');
                for (let variant = 1; variant < 10; variant++) {
                    $('ol', this).append('<li class="ui-state-default">'+variant+'</li>');
                }
                $('ol', $(this)).selectable({
                    selecting: function( event, ui ) {
                        console.log([event, ui]);

                        // pass variant & closest data- coordinates to websocket
                        let coordinates = $(event.target).parent("#sudoku>li").data();
                        console.log(coordinates);
                        conn.send(JSON.stringify({clientName:sessionStorage.getItem('clientName'), command:'setCell', coordinates:coordinates, value:$(ui.selecting).text()}));
                    }
                });

            }, function(){
                console.log(this);
                $(this).empty();
            });

        }
        if (data.top){
            $("#top").empty();
            $("#top").append('<label>Top players:</label>');
            Object.entries(data.top).forEach((client) =>{
                console.log(client);
                $("#top").append('<li>'+client[0]+':'+client[1]+'</li>');
            });


        }
    }
    catch (e) {
        console.log('Received not JSON data: '+e.toString())
    }

};
conn.onopen = function(e) {
    console.log("Connection established!");
};

