<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bootstrap demo</title>
    <style type="text/css">
      /* /\/\/\/\/\/\/\/\/ RESET STYLES /\/\/\/\/\/\/\/\/ */
      body {
        margin: 0;
        padding: 0;
      }

      img {
        border: 0 none;
        height: auto;
        line-height: 100%;
        outline: none;
        text-decoration: none;
      }

      a img {
        border: 0 none;
      }

      .imageFix {
        display: block;
      }

      table,
      td {
        border-collapse: collapse;
      }

      #bodyTable {
        height: 100% !important;
        margin: 0;
        padding: 0;
        width: 100% !important;
      }
    </style>
  </head>
  <body style="margin: 0">
    <div
      style="
        background-color: rgb(246, 247, 248);
        margin: 0;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #4f5861;
        padding: 20px;
        max-width: 500px;
        margin: auto;
      "
    >
      <header>
        <h1 style="text-align: center; color: rgba(0, 0, 0, 0.5)">
          E-Commerce
        </h1>
      </header>

      <main
        style="
          padding: 20px;
          border: 1px solid rgba(0, 0, 0, 0.5);
          border-left: 0;
          border-right: 0;
        "
      >
        <header>
          <h2 style="text-align: center">Hello we have order for you</h2>
          <p>Please check application for more details</p>
        </header>
        <main style="margin-bottom: 20px">
          <!-- table -->
          <table style="width: 100%">
            <thead>
              <tr style="border-bottom: 1px solid darkgrey">
                <th style="padding: 1rem 0.5rem">#</th>
                <th style="padding: 1rem 0.5rem">Item</th>
                <th style="padding: 1rem 0.5rem">Quantity</th>
                <th style="padding: 1rem 0.5rem">Unit Price</th>
                <th style="padding: 1rem 0.5rem">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <% loop $product %>
                <tr style="border-bottom: 1px solid darkgrey">
                  <th style="text-align: center; padding: 1rem 0.5rem">$Pos</th>
                  <td style="text-align: center; padding: 1rem 0.5rem">
                    $product_name
                  </td>
                  <td style="text-align: center; padding: 1rem 0.5rem">$quantity</td>
                  <td style="text-align: center; padding: 1rem 0.5rem">$price</td>
                  <td style="text-align: center; padding: 1rem 0.5rem">$sub_total</td>
                </tr>
              <% end_loop %>
            </tbody>
          </table>
          <!-- total -->

          <div style="width: 50%; margin-left: auto; margin-top: 20px">
            <div>
              <div style="display: inline">Total:</div>
              <div style="display: inline; float: right">$total</div>
            </div>
          </div>
        </main>
      </main>

      <!-- footer -->
      <footer
        style="text-align: center; color: rgba(0, 0, 0, 0.5); margin-top: 20px"
      >
        <small>&copy; 2022 e-commerce, All rights reserved</small>
      </footer>
    </div>
  </body>
</html>
