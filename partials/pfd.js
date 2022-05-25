jQuery(function( $ ) {
	'use strict';
	
	$(".course_tag").select2({
		placeholder: "Posts tag",
		data: pfdajax.posts
	});

	countUpdate();
	function countUpdate(){
		$(".posts_count").text($("#pfd_posts").find(".pfd_post").length);
	}

	select2Refresh();
	function select2Refresh(){
		$(".pfd_category_select, .course_temp_cat").select2({
			placeholder: "Temporary Category",
			data: pfdajax.categories
		});

		$(".pfd_post_select").select2({
			placeholder: "Post (Search)",
			data: pfdajax.posts
		});
	}

	$("#schedule_course").on("click", function(e){
		e.preventDefault();
		let course_tag = $(".course_tag").val();
		let course_date = $(".course_date").val();
		let course_duration = $(".course_duration").val();
		let course_temp_cat = $(".course_temp_cat").val();
		let course_workingdays = $(".course_workingdays").val();

		if(course_tag){
			if(course_date){
				let course = { course_tag, course_date, course_duration, course_temp_cat, course_workingdays }
	
				$.ajax({
					type: "get",
					url: pfdajax.ajaxurl,
					data: {
						action: "get_course_posts",
						nonce: pfdajax.nonce,
						course: course
					},
					beforeSend: function(){
						$("#pfd_loader").css("display", "flex");
					},
					dataType: "json",
					success: function (response) {
						$("#pfd_loader").css("display", "none");
	
						if(response.error){
							alert(response.error);
						}
	
						if(response.success){
							$("#pfd_posts").html("");
	
							response.success.forEach(post => {
								let element = `<div class="pfd_post">
								<span class="lineStatus"></span>
								<div class="pfd_input">
									<select required name="pfdposts[${post.post_id}][post]" class="pfd_post_select">
										<option selected value="${post.post_id}">${post.post_title}</option>
									</select>
								</div>
								<div class="pfd_input">
									<input required type="date" placeholder="Date" name="pfdposts[${post.post_id}][date]" value="${post.date}">
								</div>
								<div class="pfd_input">
									<input type="number" placeholder="Duration" name="pfdposts[${post.post_id}][duration]" value="${post.duration}">
								</div>
								<div class="pfd_input">
									<select required class="pfd_category_select" name="pfdposts[${post.post_id}][category]">
										<option selected value="${post.temp_cat}">${post.cat_text}</option>
									</select>
								</div>
								<span class="delete_pfd_post">+</span>
								</div>`;
	
								$("#pfd_posts").append(element);
								countUpdate();
							});
						}
					}
				});
			}else{
				alert("Please select a date.");
			}
		}else{
			alert("Please select a poost.");
		}
		
	});

	$(document).on("click", "#nextpostBtn", function(e){
		$(".notf").remove();
		e.preventDefault();
		$(this).prop("disabled", true);

		let unique = new Date().valueOf();

		let element = `<div class="pfd_post">
		<span class="lineStatus"></span>
		<div class="pfd_input">
			<select required name="pfdposts[${unique}][post]" class="pfd_post_select">
				<option value="">Post (Search)</option>
			</select>
		</div>
		<div class="pfd_input">
			<input required type="date" placeholder="Date" name="pfdposts[${unique}][date]" value="">
		</div>
		<div class="pfd_input">
			<input type="number" placeholder="Duration" name="pfdposts[${unique}][duration]" value="">
		</div>
		<div class="pfd_input">
			<select required class="pfd_category_select" name="pfdposts[${unique}][category]">
				<option value="">Temporary Category</option>
			</select>
		</div>
		<span class="delete_pfd_post">+</span>
		</div>`;

		$("#pfd_posts").append(element);
		select2Refresh();
		countUpdate();
		$(this).prop("disabled", false);
	});

	$(document).on("click", "span.delete_pfd_post", function(){
		if(confirm("You will lose the line permanently!")){
			let deletedLine = $(this).parents('.pfd_post').data("id");
			if(deletedLine !== undefined){
				$("#pfd_posts").append(`<input type="hidden" name="deletedLines[]" value="${deletedLine}">`);
			}

			$(this).parents('.pfd_post').remove();
			countUpdate();
		}
	});

});
