function tableNums() {
    var number_in_table = document.querySelectorAll(".number_in_table > p");
    let nT = 1;
    number_in_table.forEach(e => {
        e.textContent = nT
        nT++
    })
}

tableNums()

function fileControl() {
    if(document.getElementById('fileControl') !== null) {
        document.getElementById('fileControl').addEventListener('change', function(){
            if( this.value ){
                $(".uploadFile").prop("disabled", false);
            } else {
                $(".uploadFile").prop("disabled", true);
            }
        });
    }
}

fileControl()

$(document).ready(() => {
    $(document).on('click', '.pagination .page-link', function (e) {
        e.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        pagination(page)
    })

    function pagination(page) {
        $.ajax({
            url: '/users/list?page=' + page,
            success: function(data) {
                $('.table-responsive').html(data);
            }
        })
    }

    $(document).on('click', '.importUsersSubmit', () => {
        var submit = $('.importUsersSubmit');
        if($('.importUsers')[0].files.length > 0) {
            submit.prop('disabled', true);
            toastr.options = {
                "closeButton": true,
                "debug": true,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut",
            }

            const formData = new FormData();
            formData.append('_token', $("meta[name='csrf-token']").attr('content'));
            formData.append('file', $('.importUsers')[0].files[0]);

            $.ajax({
                url: '/users/import',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if(data.status === 200) {
                        toastr.options.onHidden = function() {
                            window.location.reload();
                        }
                        toastr["success"](data.message)
                    } else {
                        toastr["error"](data.message)
                        submit.prop('disabled', false);
                    }
                },
                error: function(data) {
                    toastr["error"](data.message)
                    submit.prop('disabled', false);
                }
            })
        }
    })
})

