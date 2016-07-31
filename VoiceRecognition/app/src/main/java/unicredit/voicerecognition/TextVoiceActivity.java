package unicredit.voicerecognition;

/**
 * Created by John on 7/31/2016.
 */

import android.graphics.Color;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.widget.TextView;

public class TextVoiceActivity extends AppCompatActivity{
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_text_voice);
        TextView text = (TextView) findViewById(R.id.TextBoxVR);
        text.setTextColor(Color.parseColor("#FFFF00"));
    }
}
