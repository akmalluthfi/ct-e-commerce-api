<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Change your password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor"
      crossorigin="anonymous"
    />
    <style>
      * {
        font-family: 'Nunito', sans-serif;
      }

      body {
        background-color: rgb(246, 247, 248);
      }
    </style>
  </head>
  <body>
    <div class="d-flex vh-100 align-items-center justify-content-center">
      <div class="col col-sm-8 col-md-6 col-lg-5">
        <h3 class="text-center mb-3">Change Your Password</h3>
        <div class="card shadow-sm mx-4" id="card">
          <div class="card-body">
            <form method="post" action="$link" id="form">
              <input type="hidden" name="token" value="$token" />
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                  type="password"
                  class="form-control"
                  id="password"
                  name="password"
                />
              </div>
              <div class="mb-3">
                <label for="confPassword" class="form-label"
                  >Confirm Password</label
                >
                <input
                  type="password"
                  class="form-control"
                  id="confPassword"
                  name="confPassword"
                />
                <div id="passwordHelp" class="form-text">
                  Make sure it's at least 15 characters OR at least 8 characters
                  including a number and a lowercase letter.
                </div>
              </div>
              <button type="submit" class="btn btn-primary">Submit</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.min.js"
      integrity="sha384-kjU+l4N0Yf4ZOJErLsIcvOU2qSb74wXpOhqTvwVx3OElZRweTnQ6d31fXEoRD1Jy"
      crossorigin="anonymous"
    ></script>

    <script>
      const form = document.getElementById('form');  
     
      form.addEventListener('submit', function(e) {
        e.preventDefault(); 

        // cek apakah password dan confirmation password sama 
        const password = document.getElementById('password').value; 
        const confPassword = document.getElementById('confPassword').value;   
        const token = document.querySelector('input[type="hidden"]').value;

        const help = document.getElementById('passwordHelp'); 

        if(password.length === 0 || confPassword.length === 0) {
          help.innerHTML = `<p class="text-center text-danger">Password is too short, it must be 8 or more characters long</p>`
          return; 
        }

        if(password === confPassword) {
          // selain itu kirimkan lewat 
          changePassword(password, token);
          return; 
        } else {
          help.innerHTML = `<p class="text-center text-danger">The provided details don't seem to be correct. Please try again.</p>`
          return; 
        } 
      });

      const changePassword = async (password, token) => {
          const response = await fetch('$link', {
            method: 'POST',
            headers: { 
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ password, token })
          });

          const result = await response.json(); 

          const help = document.getElementById('passwordHelp'); 

          if(!result.success) {
            help.innerHTML = `<p class="text-center text-danger">${result.message}</p>`
            return;
          }

          // tampilkan pesan bahwa password berhasil diubah
          const card = document.getElementById('card'); 
          card.innerHTML = `<div class="alert alert-success m-0" role="alert">
            Password Success to change, now you can login.
          </div>`
      }

    </script>
  </body>
</html>
