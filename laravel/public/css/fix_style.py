import re

with open('style.css', 'r') as f:
    content = f.read()

# Create backup if not exists
import os
if not os.path.exists('style.css.backup_original'):
    with open('style.css.backup_original', 'w') as f:
        f.write(content)
    print('Original backup created')

# Fix 1: .amount-tooltips-btn - replace with better visible version
old_amount_btn = '''.amount-tooltips-btn {
 display: flex;
 align-items: center;
 justify-content: center;
 height: 20px;
 margin: 8px 5px 0;
 padding: 7px;
 cursor: pointer;
 border-radius: 15px;
 background-color: #cbd4df;
 font-size: 14px;
 font-weight: 500;
 line-height: 20px;
 color: #09519e;
}'''

new_amount_btn = '''/* ORIGINAL COMMENTED - Amount buttons had poor visibility (dark blue text on light gray)
.amount-tooltips-btn {
 display: flex;
 align-items: center;
 justify-content: center;
 height: 20px;
 margin: 8px 5px 0;
 padding: 7px;
 cursor: pointer;
 border-radius: 15px;
 background-color: #cbd4df;
 font-size: 14px;
 font-weight: 500;
 line-height: 20px;
 color: #09519e;
}
END ORIGINAL */
/* FIXED - Enhanced button visibility with orange gradient and black text */
.amount-tooltips-btn {
 display: flex;
 align-items: center;
 justify-content: center;
 height: 28px;
 margin: 8px 5px 0;
 padding: 10px 16px;
 cursor: pointer;
 border-radius: 20px;
 background: linear-gradient(135deg, #FF9500, #FFA500);
 font-size: 14px;
 font-weight: 600;
 line-height: 20px;
 color: #000000;
 border: 2px solid transparent;
 box-shadow: 0 4px 12px rgba(255, 149, 0, 0.4);
 transition: all 0.3s ease;
 min-width: 60px;
}'''

content = content.replace(old_amount_btn, new_amount_btn)
print('Fixed amount-tooltips-btn')

# Fix 2: .amount-tooltips-btn:hover, .amount-tooltips-btn.active
old_hover = '''.amount-tooltips-btn:hover, .amount-tooltips-btn.active {
 color: #fff;
 background-color: #09519e;
 }'''

new_hover = '''/* ORIGINAL COMMENTED - Hover state was dark blue with white text
.amount-tooltips-btn:hover, .amount-tooltips-btn.active {
 color: #fff;
 background-color: #09519e;
}
END ORIGINAL */
/* FIXED - Enhanced hover/active state with brighter gradient */
.amount-tooltips-btn:hover, .amount-tooltips-btn.active {
 color: #000000;
 background: linear-gradient(135deg, #FFA500, #FFB520);
 border: 2px solid #000000;
 box-shadow: 0 6px 16px rgba(255, 149, 0, 0.6);
 transform: translateY(-2px);
 }'''

content = content.replace(old_hover, new_hover)
print('Fixed amount-tooltips-btn:hover')

with open('style.css', 'w') as f:
    f.write(content)

print('\nFixes applied successfully!')
print('Modified file: laravel/public/css/style.css')
