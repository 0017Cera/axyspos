from flask import Flask, render_template, request, redirect, url_for, flash, jsonify
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager, UserMixin, login_user, login_required, logout_user, current_user
from datetime import datetime
import os

app = Flask(__name__)
app.config['SECRET_KEY'] = os.urandom(24)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///purchase_orders.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

# User Model
class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password = db.Column(db.String(120), nullable=False)
    role = db.Column(db.String(20), nullable=False)  # 'admin', 'manager', 'staff'

# Purchase Order Model
class PurchaseOrder(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    po_number = db.Column(db.String(20), unique=True, nullable=False)
    supplier = db.Column(db.String(100), nullable=False)
    date_created = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    status = db.Column(db.String(20), nullable=False)  # 'draft', 'pending', 'approved', 'rejected'
    total_amount = db.Column(db.Float, nullable=False)
    created_by = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    items = db.relationship('PurchaseOrderItem', backref='purchase_order', lazy=True)

# Purchase Order Item Model
class PurchaseOrderItem(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    po_id = db.Column(db.Integer, db.ForeignKey('purchase_order.id'), nullable=False)
    item_name = db.Column(db.String(100), nullable=False)
    quantity = db.Column(db.Integer, nullable=False)
    unit_price = db.Column(db.Float, nullable=False)
    total_price = db.Column(db.Float, nullable=False)

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        user = User.query.filter_by(username=username).first()
        if user and user.password == password:  # In production, use proper password hashing
            login_user(user)
            return redirect(url_for('dashboard'))
        flash('Invalid username or password')
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('login'))

@app.route('/dashboard')
@login_required
def dashboard():
    purchase_orders = PurchaseOrder.query.filter_by(created_by=current_user.id).all()
    return render_template('dashboard.html', purchase_orders=purchase_orders)

@app.route('/create_po', methods=['GET', 'POST'])
@login_required
def create_po():
    if request.method == 'POST':
        try:
            # Create new purchase order
            po = PurchaseOrder(
                po_number=request.form['po_number'],
                supplier=request.form['supplier'],
                status='draft',
                total_amount=0,  # Will be updated after adding items
                created_by=current_user.id
            )
            db.session.add(po)
            db.session.flush()  # Get the PO ID without committing

            # Add items
            total_amount = 0
            for item_data in request.form.getlist('items'):
                item = PurchaseOrderItem(
                    po_id=po.id,
                    item_name=item_data['name'],
                    quantity=int(item_data['quantity']),
                    unit_price=float(item_data['unit_price']),
                    total_price=int(item_data['quantity']) * float(item_data['unit_price'])
                )
                total_amount += item.total_price
                db.session.add(item)

            # Update total amount
            po.total_amount = total_amount
            db.session.commit()
            flash('Purchase Order created successfully!')
            return redirect(url_for('dashboard'))
        except Exception as e:
            db.session.rollback()
            flash('Error creating Purchase Order. Please try again.')
            return redirect(url_for('create_po'))

    return render_template('create_po.html')

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
    app.run(debug=True) 