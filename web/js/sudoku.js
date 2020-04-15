console.log('Sudoku client started');
var conn = new WebSocket('ws://localhost:8080');
conn.onmessage = function(e) {
    console.log('Response:' + e.data);
    // try process received data as json
    try {
        let data = '';
        data = e.data;
        data = JSON.parse(data);
        if (data.matrix){
            console.log('Matrix received:');
            let matrix = Object.values(data.matrix);
            matrix.forEach(column =>{
                column = Object.values(column);
                column.forEach(cell =>{
                        if (cell){
                            $("#sudoku").append('<li class="ui-state-disabled">'+cell+'</li>');
                        }
                        else {
                            $("#sudoku").append('<li class="ui-state-default" title="Click to set value"></li>');
                        }
                    }
                );
            });
        }
    }
    catch (e) {
        console.log('Received not JSON data: '+e.toString())
    }

};
conn.onopen = function(e) {
    console.log("Connection established!");
    console.log('Hey!');
    conn.send('Hey!');
};