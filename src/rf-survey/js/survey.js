(function($) {

    var question = 0;

    $('.survey-fullpage .the-survey li:eq('+question+')').css({'display': 'block', 'top': '10%', 'opacity': 1 });
    $('.qnumber-current').text(0);
    $('.qnumber-total').text($('.the-survey li').length -1);
    
    // Fix labels in IE8
    $("label").click(function(){
        if ($(this).attr("for") != "") {
            $("#" + $(this).attr("for")).attr('selected',true).click();
        }

        if($(this).siblings('input[type=radio]').length) {
            $(this).closest('li').find('input').parent().removeClass('checked');
            $(this).parent().addClass('checked');
        } else {
            $(this).parent().toggleClass('checked');
        }
        if($(this).parent().hasClass('checked')) {
            $("#" + $(this).attr("for")).attr('selected',true).click();
        } else {
            $("#" + $(this).attr("for")).attr('selected',false).click();
        }
    });

    $('body').addClass('hide-survey-footer');
    var $questionNumber = $('.qnumber-current'),
        numberOfQuestions = $('.the-survey li').length;
    $('.form-next, .form-prev').on('click', function(e){
        e.preventDefault();

        $('body').removeClass('hide-survey-footer');


        var $question = $('.the-survey li:eq('+question+')');
        var required = $question.data('required');

        console.log(required);

        if(required) {
            if (!$question.find('input[type=text], textarea').val() && !$question.find('input[type=radio]:checked, input[type=checkbox]:checked, select').val()) {
                $($question).addClass('required-error');
                return;
            } else {
                $($question).removeClass('required-error');
            }
        }

        var nextQuestion = question;
        if ($(this).hasClass('form-next')) {
            nextQuestion++;
        } else {
            nextQuestion--;
        }

        if (nextQuestion <= 0 && nextQuestion > numberOfQuestions - 1) {
            return;
        }

        $('.form-prev').css('display', 'inline-block');
        if (nextQuestion == 0) {
            $('.form-prev').hide();
            $('body').addClass('hide-survey-footer');
        }

        // Hide current question
        // var top = -$('.the-survey li').height();
        // if ($(this).hasClass('form-prev')) {
        //     top = '100%';
        // }
        $('.survey-fullpage .the-survey li:eq('+question+')').css({'display': 'none'});

        // Get next question
        var $nextQuestion = $('.the-survey li:eq('+nextQuestion+')');

        // Update question number and progress bar
        $questionNumber.text(nextQuestion);
        var currentProgress = (nextQuestion)/numberOfQuestions * 100;
        $('.progressbar').css('width', currentProgress + '%');


        // Show next question
        $nextQuestion.css({'display': 'block'});

        // Show submit button
        if (nextQuestion == numberOfQuestions - 1) {
            $('body').addClass('show-submit');
        } else {
            $('body').removeClass('show-submit');
        }

        // Store current question index
        question = nextQuestion;

    });


    $('.survey-container .submit').on('click', function(e){
        e.preventDefault();

        var formData = $('#survey').serialize();

        $.ajax({
            type: 'POST',
            url: $('#survey').attr('action'),
            data: formData,
            success: function() {
                $('body').addClass('survey-complete');
            }
        });
        return false;


    });


    $('.form-prev').on('click', function(e){
        e.preventDefault();
    });

})(jQuery);