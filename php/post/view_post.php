<?php
session_start();

// Database connection
include '../db_connect.php';

// Fetch the post by ID
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT posts.*, categories.name AS category_name, users.username FROM posts JOIN categories ON posts.category_id = categories.id JOIN users ON posts.user_id = users.user_id WHERE posts.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    echo "<h1>Post not found</h1>";
    exit;
}

// Fetch comments and their replies based on the selected filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'highest_score';

$order_by = 'c1.created_at DESC'; // Default order
switch ($filter) {
    case 'newest':
        $order_by = 'c1.created_at DESC';
        break;
    case 'most_replies':
        $order_by = '(SELECT COUNT(*) FROM comments c2 WHERE c2.parent_id = c1.id) DESC';
        break;
    case 'highest_score':
    default:
        $order_by = '(c1.upvotes - c1.downvotes) DESC'; // Assuming you have upvotes and downvotes columns
        break;
}

$comments_stmt = $conn->prepare("
    SELECT c1.*, users.username, 
        (SELECT COUNT(*) FROM comments c2 WHERE c2.parent_id = c1.id) AS reply_count 
    FROM comments c1 
    JOIN users ON c1.user_id = users.user_id 
    WHERE c1.post_id = ? AND c1.parent_id IS NULL 
    ORDER BY $order_by
");
$comments_stmt->bind_param("i", $post_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $row['replies'] = [];
    $reply_stmt = $conn->prepare("
        SELECT replies.*, users.username 
        FROM comments replies 
        JOIN users ON replies.user_id = users.user_id 
        WHERE replies.parent_id = ? 
        ORDER BY replies.created_at ASC
    ");
    $reply_stmt->bind_param("i", $row['id']);
    $reply_stmt->execute();
    $reply_result = $reply_stmt->get_result();
    while ($reply = $reply_result->fetch_assoc()) {
        $row['replies'][] = $reply;
    }
    $comments[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">

<head> <?php include '../include/header.php'; ?>
	<!-- Include Highlight.js -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
	<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
	<link rel="stylesheet" href="../../css/css2.css">
	<script>
	document.addEventListener('DOMContentLoaded', () => {
		hljs.highlightAll();
	});
	</script>
	<style>
	pre {
		padding: 1rem;
		border-radius: 0.25rem;
		overflow: auto;
	}

	code {
		font-family: 'Courier New', monospace;
		font-size: 0.9rem;
	}

	.media-container {
		display: flex;
		justify-content: center;
		flex-wrap: wrap;
		gap: 1rem;
	}

	.media-item {
		width: 150px;
		height: 100px;
		overflow: hidden;
		position: relative;
		border: 1px solid #ddd;
		border-radius: 5px;
	}

	.media-item img,
	.media-item video {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.fullscreen-btn {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		background-color: rgba(0, 0, 0, 0.5);
		color: white;
		border: none;
		padding: 0.5rem 1rem;
		border-radius: 5px;
		cursor: pointer;
		z-index: 10;
	}

	.fullscreen-btn:hover {
		background-color: rgba(0, 0, 0, 0.8);
	}

	.exit-fullscreen-btn {
		position: fixed;
		top: 10px;
		right: 10px;
		background-color: rgba(0, 0, 0, 0.7);
		color: white;
		border: none;
		padding: 0.5rem 1rem;
		border-radius: 5px;
		cursor: pointer;
		z-index: 1000;
		display: none;
	}

	.upvote-btn.active,
	.downvote-btn.active {
		background-color: #28a745 !important;
		/* Upvote color */
		color: white;
	}

	.downvote-btn.active {
		background-color: #dc3545 !important;
		/* Downvote color */
		color: white;
	}

	/* Style for Quill placeholder */
	.ql-editor.ql-blank::before {
		color: #6c757d;
		/* This is the Bootstrap 'text-body-tertiary' color */
	}
	</style>
</head>

<body>
	<div class="container mt-5">
		<a href="../../index.php" class="btn btn-primary mb-3">Back to Main Page</a>
		<div class="card">
			<div class="card-body">
				<!-- Post Header -->
				<div class="text-center mb-4">
					<h1 class="card-title mb-1"><?= htmlspecialchars($post['title'] ?? 'No Title') ?></h1>
					<p class="mb-0"><em>Posted on <?= date('F j, Y, g:i A', strtotime($post['created_at'])) ?></em></p>
					<p class="mb-0">By <strong><?= htmlspecialchars($post['username'] ?? 'Anonymous') ?></strong></p>
					<p><strong>Category:</strong> <?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></p>
					<p><strong>Hashtags:</strong> <?= htmlspecialchars($post['hashtags'] ?? '') ?></p>
				</div>
				<hr>
				<!-- Post Content -->
				<div> <?= $post['content'] ?> </div> <?php
            // Decode attachments data
            $images = !empty($post['images']) ? json_decode($post['images'], true) : [];
            $videos = !empty($post['videos']) ? json_decode($post['videos'], true) : [];

            // Check if there are any attachments
            if (!empty($images) || !empty($videos)): ?>
				<hr>
				<!-- Display Images and Videos -->
				<h6 class='text-center'><i class="bi bi-paperclip"></i>Attachments<i class="bi bi-paperclip"></i></h6>
				<div class="media-container"> <?php if (!empty($images)): ?> <?php foreach ($images as $image): ?> <div class="media-item">
						<img src="<?= htmlspecialchars($image) ?>" alt="Post Image">
						<button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
					</div> <?php endforeach; ?> <?php endif; ?> <?php if (!empty($videos)): ?> <?php foreach ($videos as $video_path): ?> <div class="media-item">
						<video>
							<source src="<?= htmlspecialchars($video_path) ?>" type="video/mp4"> Your browser does not support the video tag. </video>
						<button class="fullscreen-btn" onclick="toggleFullscreen(event)">⛶</button>
					</div> <?php endforeach; ?> <?php endif; ?> </div> <?php endif; ?>
			</div>
		</div>
	</div>
	<div class="text-center mt-3">
		<button class="btn btn-outline-success me-2 upvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-post-id="<?= $post_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
			<i class="bi bi-caret-up-fill"></i> <span id="upvote-count"><?= $post['upvotes'] ?></span>
		</button>
		<button class="btn btn-outline-danger downvote-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-post-id="<?= $post_id ?>" <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>>
			<i class="bi bi-caret-down-fill"></i> <span id="downvote-count"><?= $post['downvotes'] ?></span>
		</button>
		<p class="mt-2">Score: <span id="score"><?= $post['upvotes'] - $post['downvotes'] ?></span></p>
	</div> <?php if (!isset($_SESSION['user_id'])): ?> <p class="text-center">You must <a href="../sign_in/sign_in_html.php" class="text-decoration-none">sign in</a> to vote.</p> <?php endif; ?>
	<!-- Comment Section -->
	<div class="card container">
		<div class="card-body">
			<h4>Comments</h4> <?php if (isset($_SESSION['user_id'])): ?> <div id="comment-editor" class="mb-3"></div>
			<button class="btn btn-primary" id="submit-comment" data-post-id="<?= $post_id ?>">Submit Comment</button> <?php else: ?> <p class="">You must <a href="../sign_in/sign_in_html.php" class="text-decoration-none">sign in</a> to comment and reply.</p> <?php endif; ?>
			<hr>
            <div class="mb-3">
                <label for="filter" class="form-label">Filter Comments:</label>
                <select name="filter" id="filter" class="form-select me-2 bg-dark text-light">
                    <option value="highest_score" <?= isset($_GET['filter']) && $_GET['filter'] == 'highest_score' ? 'selected' : '' ?>>Highest Score</option>
                    <option value="newest" <?= isset($_GET['filter']) && $_GET['filter'] == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="most_replies" <?= isset($_GET['filter']) && $_GET['filter'] == 'most_replies' ? 'selected' : '' ?>>Most Replies</option>
                </select>
            </div>
			<div id="comments-container"> <?php foreach ($comments as $comment): ?> <div class="card mb-3" style="max-width: 100%;">
					<div class="card-body">
						<!-- Comment Content -->
						<h6 class="card-subtitle mb-2"><?= htmlspecialchars($comment['username']) ?> - <?= date('F j, Y, g:i A', strtotime($comment['created_at'])) ?></h6>
						<p class="card-text"><?= $comment['content'] ?></p>
						<!-- Upvote and Downvote Buttons for Comments -->
						<button class="btn btn-outline-success me-2 upvote-comment-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $comment['id'] ?>">
							<i class="bi bi-caret-up-fill"></i> <span class="upvote-count"><?= $comment['upvotes'] ?? 0 ?></span>
						</button>
						<button class="btn btn-outline-danger downvote-comment-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $comment['id'] ?>">
							<i class="bi bi-caret-down-fill"></i> <span class="downvote-count"><?= $comment['downvotes'] ?? 0 ?></span>
						</button>
						<a class="btn btn-link text-decoration-none reply-btn" data-comment-id="<?= $comment['id'] ?>">Reply</a>
						<!-- Hide/Show Replies Button -->
						<a class="btn btn-link text-decoration-none toggle-replies-btn" data-comment-id="<?= $comment['id'] ?>" data-reply-count="<?= $comment['reply_count'] ?>"> Show Replies (<?= $comment['reply_count'] ?>) </a>
						<!-- Replies Section -->
						<div class="replies ms-4 mt-3" style="display: none;"> <?php foreach ($comment['replies'] as $reply): ?> <div class="card mb-2" style="max-width: 100%;">
								<div class="card-body">
									<!-- Reply Content -->
									<h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($reply['username']) ?> - <?= date('F j, Y, g:i A', strtotime($reply['created_at'])) ?></h6>
									<p class="card-text"><?= $reply['content'] ?></p>
									<!-- Upvote and Downvote Buttons for Replies -->
									<button class="btn btn-outline-success me-3 upvote-reply-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $reply['id'] ?>">
										<i class="bi bi-caret-up-fill"></i> <span class="upvote-count"><?= $reply['upvotes'] ?? 0 ?></span>
									</button>
									<button class="btn btn-outline-danger me-3 downvote-reply-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>" data-comment-id="<?= $reply['id'] ?>">
										<i class="bi bi-caret-down-fill"></i> <span class="downvote-count"><?= $reply['downvotes'] ?? 0 ?></span>
									</button>
								</div>
							</div> <?php endforeach; ?> </div>
					</div>
				</div> <?php endforeach; ?> </div>
		</div>
	</div>
	<br> <?php $conn->close(); ?> <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
	<script>
	function toggleFullscreen(event) {
		const media = event.target.parentElement.querySelector('img, video');
		if(media.requestFullscreen) {
			media.requestFullscreen();
		} else if(media.webkitRequestFullscreen) { // Safari
			media.webkitRequestFullscreen();
		} else if(media.msRequestFullscreen) { // IE11
			media.msRequestFullscreen();
		}
	}
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.upvote-btn, .downvote-btn').forEach(button => {
			button.addEventListener('click', () => {
				// Check if the button is disabled
				if(button.disabled) {
					alert('You must log in to vote.');
					return;
				}
				const postId = button.dataset.postId;
				const voteType = button.classList.contains('upvote-btn') ? 'upvote' : 'downvote';
				fetch('vote.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `post_id=${postId}&vote_type=${voteType}`
				}).then(response => response.json()).then(data => {
					if(data.success) {
						document.getElementById('upvote-count').textContent = data.upvotes;
						document.getElementById('downvote-count').textContent = data.downvotes;
						document.getElementById('score').textContent = data.score;
						// Update button states
						document.querySelectorAll('.upvote-btn, .downvote-btn').forEach(btn => {
							btn.classList.remove('active');
						});
						if(voteType === 'upvote') {
							button.classList.toggle('active');
						} else {
							button.classList.toggle('active');
						}
					} else {
						alert(data.error || 'An error occurred');
					}
				}).catch(err => console.error('Error:', err));
			});
		});
	});
	document.addEventListener('DOMContentLoaded', () => {
		// Define the toolbar options
		const alertDiv = document.createElement('div');
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.left = '50%';
    alertDiv.style.transform = 'translateX(-50%)';
    alertDiv.style.zIndex = '1050';
    alertDiv.style.display = 'none';
    document.body.appendChild(alertDiv);

    function showAlert(message) {
        alertDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertDiv.style.display = 'block';
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 3000);
    }

    const toolbarOptions = [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        ['blockquote', 'code-block'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link']
    ];

    const quill = new Quill('#comment-editor', {
        theme: 'snow',
        placeholder: 'Write your comment...',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // Get post ID from submit button
    const postId = document.getElementById('submit-comment').getAttribute('data-post-id');

    document.getElementById('submit-comment').addEventListener('click', () => {
        const content = quill.root.innerHTML;
        const plainText = quill.getText().trim();

        if (!plainText) {
            showAlert('Comment cannot be empty.');
            return;
        }

        fetch('submit_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}&content=${encodeURIComponent(content)}`
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert(data.error || 'An error occurred.');
            }
        }).catch(err => console.error('Error:', err));
    });

    document.getElementById('comments-container').addEventListener('click', (e) => {
        if (e.target.classList.contains('reply-btn')) {
            const parentCommentId = e.target.dataset.commentId;
            let replyEditor = document.getElementById(`reply-editor-${parentCommentId}`);
            let submitReplyButton = document.getElementById(`submit-reply-${parentCommentId}`);
            let replyQuill;

            if (!replyEditor) {
                replyEditor = document.createElement('div');
                replyEditor.id = `reply-editor-${parentCommentId}`;
                replyEditor.classList.add('mb-3');
                e.target.parentElement.appendChild(replyEditor);

                replyQuill = new Quill(replyEditor, {
                    theme: 'snow',
                    placeholder: 'Write your reply...',
                    modules: {
                        toolbar: toolbarOptions
                    }
                });

                submitReplyButton = document.createElement('button');
                submitReplyButton.id = `submit-reply-${parentCommentId}`;
                submitReplyButton.textContent = 'Submit Reply';
                submitReplyButton.classList.add('btn', 'btn-primary', 'mt-2');
                e.target.parentElement.appendChild(submitReplyButton);

                submitReplyButton.addEventListener('click', () => {
                    const content = replyQuill.root.innerHTML;
                    const plainText = replyQuill.getText().trim();

                    if (!plainText) {
                        showAlert('Reply cannot be empty.');
                        return;
                    }

                    fetch('submit_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `post_id=${postId}&parent_id=${parentCommentId}&content=${encodeURIComponent(content)}`
                    }).then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            showAlert(data.error || 'An error occurred.');
                        }
                    }).catch(err => console.error('Error:', err));
                });
            } else {
                replyQuill = Quill.find(replyEditor);
            }

            const isVisible = replyEditor.style.display === 'block';
            if (isVisible) {
                replyEditor.style.display = 'none';
                submitReplyButton.style.display = 'none';
                replyQuill.getModule('toolbar').container.style.display = 'none';
            } else {
                replyEditor.style.display = 'block';
                submitReplyButton.style.display = 'block';
                replyQuill.getModule('toolbar').container.style.display = 'block';
                replyQuill.getModule('toolbar').container.classList.add('mt-3');
            }
        }
    });
});
	document.addEventListener('DOMContentLoaded', () => {
		// Voting for comments
		document.querySelectorAll('.upvote-comment-btn, .downvote-comment-btn').forEach(button => {
			button.addEventListener('click', () => {
				if(button.disabled) {
					alert('You must log in to vote.');
					return;
				}
				const commentId = button.dataset.commentId;
				const voteType = button.classList.contains('upvote-comment-btn') ? 'upvote' : 'downvote';
				fetch('comment_vote.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `comment_id=${commentId}&vote_type=${voteType}`
				}).then(response => response.json()).then(data => {
					if(data.success) {
						button.querySelector('.upvote-count, .downvote-count').textContent = voteType === 'upvote' ? data.upvotes : data.downvotes;
						// Update button states
						document.querySelectorAll('.upvote-comment-btn, .downvote-comment-btn').forEach(btn => {
							btn.classList.remove('active');
						});
						button.classList.toggle('active');
					} else {
						alert(data.error || 'An error occurred');
					}
				}).catch(err => console.error('Error:', err));
			});
		});
		// Voting for replies (similar to comments)
		document.querySelectorAll('.upvote-reply-btn, .downvote-reply-btn').forEach(button => {
			button.addEventListener('click', () => {
				if(button.disabled) {
					alert('You must log in to vote.');
					return;
				}
				const replyId = button.dataset.commentId;
				const voteType = button.classList.contains('upvote-reply-btn') ? 'upvote' : 'downvote';
				fetch('comment_vote.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `comment_id=${replyId}&vote_type=${voteType}`
				}).then(response => response.json()).then(data => {
					if(data.success) {
						button.querySelector('.upvote-count, .downvote-count').textContent = voteType === 'upvote' ? data.upvotes : data.downvotes;
						// Update button states
						document.querySelectorAll('.upvote-reply-btn, .downvote-reply-btn').forEach(btn => {
							btn.classList.remove('active');
						});
						button.classList.toggle('active');
					} else {
						alert(data.error || 'An error occurred');
					}
				}).catch(err => console.error('Error:', err));
			});
		});
	});
	document.addEventListener('DOMContentLoaded', () => {
		// Existing code...
		// Toggle replies visibility
		document.querySelectorAll('.toggle-replies-btn').forEach(button => {
			button.addEventListener('click', () => {
				const commentId = button.dataset.commentId;
				const repliesContainer = button.closest('.card-body').querySelector('.replies');
				if(repliesContainer.style.display === 'none' || repliesContainer.style.display === '') {
					repliesContainer.style.display = 'block';
					button.textContent = `Hide Replies (${button.dataset.replyCount})`;
				} else {
					repliesContainer.style.display = 'none';
					button.textContent = `Show Replies (${button.dataset.replyCount})`;
				}
			});
		});
	});

    document.getElementById('filter').addEventListener('change', function() {
        const selectedFilter = this.value;
        const url = new URL(window.location.href);
        url.searchParams.set('filter', selectedFilter);
        window.location.href = url.toString();
    });
	</script>
</body>